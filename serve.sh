#!/bin/zsh

serve_directory=${1:-example.site}

if [[ ! -d "$serve_directory" ]]; then
  echo "Directory not found: $serve_directory" >&2
  exit 1
fi

front_controller="$serve_directory/public/index.php"

if [[ ! -f "$front_controller" ]]; then
  echo "Front controller not found: $front_controller" >&2
  exit 1
fi

php -S localhost:8000 -t "$serve_directory" "$front_controller"
