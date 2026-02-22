#!/bin/zsh

serve_directory=${1:-public}

if [[ ! -d "$serve_directory" ]]; then
  echo "Directory not found: $serve_directory" >&2
  exit 1
fi

php -S localhost:8000 -t "$serve_directory"
