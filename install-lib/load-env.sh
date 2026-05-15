#!/usr/bin/env bash
# Chargé par install, build, codecheck et bin/php — aligné sur ApplicationManager.

BUILD_WHEN_INSTALL="${BUILD_WHEN_INSTALL:-false}"
DOCKER_COMPOSE_FILES="${DOCKER_COMPOSE_FILES:--f docker-compose.yml}"
DOCKER_DEV="${DOCKER_DEV:-false}"

function load_env() {
  ENV_LOCAL_FILE="./.env.local"
  ENV_FILE="./.env"
  _load_env_if_not_exist_from_file "$ENV_FILE"
  _load_env_if_not_exist_from_file "$ENV_LOCAL_FILE"
  if [ "${DOCKER_DEV}" = true ]; then
    DOCKER_COMPOSE_FILES="-f docker-compose.yml -f docker-compose.dev.yml"
  fi
  export DOCKER_COMPOSE_FILES
  export DOCKER_PHP_BUILT_IMAGE="${DOCKER_PHP_BUILT_IMAGE:-}"
}

function _load_env_if_not_exist_from_file() {
  if [ -f "$1" ]; then
    while IFS= read -r line || [ -n "$line" ]; do
      if [ -n "$line" ] && [[ ! "$line" =~ ^# ]]; then
        varname=$(echo "$line" | cut -d= -f1)
        varvalue=$(echo "$line" | cut -d= -f2-)
        if [ -z "${!varname:-}" ]; then
          export "$varname=$varvalue"
        fi
      fi
    done <"$1"
  fi
}
