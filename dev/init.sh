#!/bin/bash

[ -f ./.env ] || cp ./dev/.env.template ./.env
echo ".env ok"
