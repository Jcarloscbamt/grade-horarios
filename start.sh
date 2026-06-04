#!/bin/bash
echo "🧹 Limpando sessões antigas..."
rm -rf storage/framework/sessions/*
echo "🧹 Limpando cache..."
php artisan config:clear
echo "🚀 Iniciando o servidor..."
php artisan serve
