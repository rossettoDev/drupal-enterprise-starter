#!/usr/bin/env bash
set -euo pipefail

echo "=========================================="
echo "Instalação do Drupal Enterprise Starter"
echo "=========================================="
echo ""

# Validações iniciais
if [ ! -f "composer.json" ]; then
    echo "❌ ERRO: Execute este script a partir da raiz do projeto."
    exit 1
fi

if [ ! -d "config/sync" ] || [ -z "$(ls -A config/sync 2>/dev/null)" ]; then
    echo "❌ ERRO: Diretório config/sync vazio ou inexistente."
    echo "   A configuração versionada é necessária para instalação."
    exit 1
fi

CONFIG_COUNT=$(find config/sync -name "*.yml" | wc -l)
if [ "$CONFIG_COUNT" -lt 10 ]; then
    echo "❌ ERRO: Configuração insuficiente em config/sync (encontrados: $CONFIG_COUNT arquivos)."
    exit 1
fi

echo "✓ Diretório config/sync encontrado com $CONFIG_COUNT arquivos de configuração."
echo ""

# Verificar se o Lando está rodando
if ! command -v lando &> /dev/null; then
    echo "❌ ERRO: Lando não encontrado no PATH."
    echo "   Instale o Lando: https://docs.lando.dev/install/"
    exit 1
fi

echo "⏳ Verificando status do Lando..."
if ! lando info &> /dev/null; then
    echo "⚠️  Ambiente Lando não está rodando. Iniciando..."
    lando start
else
    echo "✓ Ambiente Lando rodando."
fi
echo ""

# Aguardar o banco de dados estar pronto
echo "⏳ Aguardando banco de dados MariaDB..."
MAX_ATTEMPTS=30
ATTEMPT=0
until lando mysql -e "SELECT 1;" &> /dev/null; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
        echo "❌ ERRO: Banco de dados não respondeu após ${MAX_ATTEMPTS} tentativas."
        exit 1
    fi
    sleep 2
done
echo "✓ Banco de dados MariaDB pronto."
echo ""

# Limpar banco existente (se houver)
echo "🧹 Limpando banco de dados existente..."
lando mysql -e "DROP DATABASE IF EXISTS drupal11; CREATE DATABASE drupal11 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "✓ Banco de dados drupal11 recriado."
echo ""

# Instalar dependências se necessário
if [ ! -d "vendor" ] || [ ! -d "web/core" ]; then
    echo "📦 Instalando dependências com Composer..."
    lando composer install --no-interaction --prefer-dist
    echo "✓ Dependências instaladas."
    echo ""
fi

# Instalar o Drupal com a configuração existente
echo "🚀 Instalando Drupal a partir da configuração versionada..."
echo "   Este processo pode levar alguns minutos..."
echo ""

# O --existing-config instala o site e importa toda a configuração de config/sync
# Não é necessário especificar --db-url pois o settings.php do Lando já existe
lando drush site:install standard \
  --existing-config \
  --account-name=admin \
  --account-pass=admin \
  --site-name="Drupal Enterprise Starter" \
  -y

echo ""
echo "=========================================="
echo "✅ Instalação concluída com sucesso!"
echo "=========================================="
echo ""
echo "Acesse o site:"
echo "  🌐 URL: https://drupal-enterprise-starter.lndo.site"
echo "  👤 Usuário: admin"
echo "  🔑 Senha: admin"
echo ""
echo "Comandos úteis:"
echo "  lando drush status          # Status do site"
echo "  lando drush cr              # Limpar cache"
echo "  lando drush cex -y          # Exportar configuração"
echo "  lando drush updb -y         # Aplicar updates de banco"
echo ""
