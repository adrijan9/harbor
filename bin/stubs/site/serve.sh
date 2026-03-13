#!/bin/zsh

if (( $# > 1 )); then
  echo "Usage: ./serve.sh [start-port]" >&2
  exit 1
fi

start_port=${1:-8000}
script_directory=$(cd -- "$(dirname -- "$0")" && pwd)
public_directory="$script_directory/public"
front_controller="$public_directory/index.php"

if [[ "$start_port" != <-> ]]; then
  echo "Invalid port: $start_port" >&2
  exit 1
fi

if (( start_port < 1 || start_port > 65535 )); then
  echo "Port out of range: $start_port" >&2
  exit 1
fi

if [[ ! -d "$public_directory" ]]; then
  echo "Public directory not found: $public_directory" >&2
  exit 1
fi

if [[ ! -f "$front_controller" ]]; then
  echo "Front controller not found: $front_controller" >&2
  exit 1
fi

is_port_available() {
  local port="$1"

  if command -v lsof >/dev/null 2>&1; then
    if lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; then
      return 1
    fi

    return 0
  fi

  if command -v netstat >/dev/null 2>&1; then
    if netstat -an 2>/dev/null | grep -E "[\.:]$port[[:space:]].*LISTEN" >/dev/null; then
      return 1
    fi

    return 0
  fi

  php -r '
    $port = (int) ($argv[1] ?? 0);
    $socket = @stream_socket_server(sprintf("tcp://127.0.0.1:%d", $port), $error_number, $error_message);
    if (false === $socket) {
        exit(1);
    }

    fclose($socket);
    exit(0);
  ' "$port" >/dev/null 2>&1
}

find_available_port() {
  local port="$1"

  while (( port <= 65535 )); do
    if is_port_available "$port"; then
      echo "$port"
      return 0
    fi

    ((port++))
  done

  return 1
}

resolved_port=$(find_available_port "$start_port")
if [[ -z "$resolved_port" ]]; then
  echo "Unable to find an available port starting from $start_port." >&2
  exit 1
fi

if [[ "$resolved_port" != "$start_port" ]]; then
  echo "Port $start_port is in use. Using $resolved_port instead."
fi

echo "Serving \"$public_directory\" at http://127.0.0.1:$resolved_port"
exec php -S "127.0.0.1:$resolved_port" -t "$public_directory" "$front_controller"
