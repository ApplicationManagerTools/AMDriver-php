#!/usr/bin/env sh
# Copie la spec récepteur OpenAPI du bundle vers l'application gérée hôte.
# Usage (depuis la racine du projet Symfony) :
#   vendor/application-manager-tools/am-driver/docs/openapi/kit/sync-openapi.sh
#   vendor/.../sync-openapi.sh ./docker/swagger/am-driver

set -e

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
SRC="${SCRIPT_DIR}/../receptacle-v1.yaml"
DEST_DIR=${1:-./docker/swagger/am-driver}

if [ ! -f "$SRC" ]; then
  echo "Source introuvable: $SRC" >&2
  exit 1
fi

mkdir -p "$DEST_DIR"
cp "$SRC" "${DEST_DIR}/receptacle-v1.yaml"
echo "Copié vers ${DEST_DIR}/receptacle-v1.yaml"
echo "Éditez la section servers (host + route_prefix) puis lancez swagger-ui-am-driver."
