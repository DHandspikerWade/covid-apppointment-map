# See here for image contents: https://github.com/microsoft/vscode-dev-containers/tree/v0.158.0/containers/php/.devcontainer/base.Dockerfile

# [Choice] PHP version: 8, 8.0, 7, 7.4, 7.3
ARG VARIANT="8"
FROM mcr.microsoft.com/vscode/devcontainers/php:0-${VARIANT}

# [Option] Install Node.js
ARG INSTALL_NODE="true"
ARG NODE_VERSION="lts/*"
RUN if [ "${INSTALL_NODE}" = "true" ]; then su vscode -c "umask 0002 && . /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends jq
