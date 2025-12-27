#!/bin/bash

#===============================================================================
# TablesPro - Initialization Script
#===============================================================================
# Ta skripta pripravi razvojno okolje za TablesPro projekt.
#
# Uporaba:
#   chmod +x init.sh
#   ./init.sh
#===============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print functions
print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

#===============================================================================
# Check Prerequisites
#===============================================================================
print_header "Preverjanje predpogojev"

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    print_success "Node.js: $NODE_VERSION"
else
    print_error "Node.js ni nameščen!"
    echo "  Namesti z: sudo apt install nodejs"
    exit 1
fi

# Check npm
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    print_success "npm: v$NPM_VERSION"
else
    print_error "npm ni nameščen!"
    exit 1
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    print_success "PHP: $PHP_VERSION"
else
    print_error "PHP ni nameščen!"
    echo "  Namesti z: sudo apt install php"
    exit 1
fi

# Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer -V | head -n 1)
    print_success "Composer: $COMPOSER_VERSION"
else
    print_warning "Composer ni nameščen!"
    echo "  Namesti z: sudo apt install composer"
    echo "  Nadaljujem brez Composer..."
fi

#===============================================================================
# Install Dependencies
#===============================================================================
print_header "Nameščanje odvisnosti"

# Install npm dependencies
print_info "Nameščam npm dependencies..."
if npm ci; then
    print_success "npm dependencies nameščeni"
else
    print_warning "npm ci ni uspel, poskušam npm install..."
    npm install
fi

# Install Composer dependencies (if Composer is available)
if command -v composer &> /dev/null; then
    print_info "Nameščam Composer dependencies..."
    if composer install --no-dev; then
        print_success "Composer dependencies nameščeni"
    else
        print_warning "Composer install ni uspel"
    fi
fi

#===============================================================================
# Build Project
#===============================================================================
print_header "Gradnja projekta"

print_info "Gradim za development..."
if npm run dev; then
    print_success "Development build uspešen"
else
    print_error "Build ni uspel!"
    exit 1
fi

#===============================================================================
# Summary
#===============================================================================
print_header "Inicializacija končana!"

echo -e "
${GREEN}TablesPro je pripravljen za razvoj!${NC}

${YELLOW}Naslednji koraki:${NC}

1. ${BLUE}Preberi dokumentacijo:${NC}
   - docs/planning/DEVELOPMENT_PLAN.md
   - docs/planning/FAZA1_BARVNI_STATUSI.md
   - docs/planning/FAZA2_SUMMARY_VRSTICE.md
   - docs/planning/FAZA3_ROW_GROUPS.md

2. ${BLUE}Zaženi development server:${NC}
   npm run watch

3. ${BLUE}Za production build:${NC}
   npm run build

4. ${BLUE}Kopiraj v Nextcloud:${NC}
   cp -r . /path/to/nextcloud/apps/tablespro

${YELLOW}Git workflow:${NC}
   git checkout -b feature/colored-status
   # ... naredi spremembe ...
   git add .
   git commit -m \"Add colored status feature\"
   git push origin feature/colored-status

${BLUE}Happy coding! 🚀${NC}
"
