#!/bin/bash

# =============================================
# SCRIPT DE INSTALACIÓN - CHAT EN TIEMPO REAL
# Sistema ISHUME
# =============================================

echo "🚀 Instalando Sistema de Chat en Tiempo Real - ISHUME"
echo "=================================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes con color
print_message() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar si Node.js está instalado
check_nodejs() {
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_message "Node.js encontrado: $NODE_VERSION"
        return 0
    else
        print_error "Node.js no está instalado"
        print_info "Por favor instala Node.js desde: https://nodejs.org/"
        return 1
    fi
}

# Verificar si npm está instalado
check_npm() {
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version)
        print_message "npm encontrado: $NPM_VERSION"
        return 0
    else
        print_error "npm no está instalado"
        return 1
    fi
}

# Verificar si MySQL está disponible
check_mysql() {
    if command -v mysql &> /dev/null; then
        print_message "MySQL encontrado"
        return 0
    else
        print_warning "MySQL no encontrado en PATH"
        print_info "Asegúrate de que MySQL esté instalado y funcionando"
        return 1
    fi
}

# Instalar dependencias de Node.js
install_dependencies() {
    print_info "Instalando dependencias de Node.js..."
    
    if npm install; then
        print_message "Dependencias instaladas correctamente"
        return 0
    else
        print_error "Error instalando dependencias"
        return 1
    fi
}

# Configurar base de datos
setup_database() {
    print_info "Configurando base de datos..."
    
    # Solicitar credenciales de MySQL
    echo -n "Ingresa el usuario de MySQL (por defecto: root): "
    read MYSQL_USER
    MYSQL_USER=${MYSQL_USER:-root}
    
    echo -n "Ingresa la contraseña de MySQL: "
    read -s MYSQL_PASSWORD
    echo
    
    echo -n "Ingresa el nombre de la base de datos (por defecto: appishume): "
    read MYSQL_DATABASE
    MYSQL_DATABASE=${MYSQL_DATABASE:-appishume}
    
    echo -n "Ingresa el host de MySQL (por defecto: localhost): "
    read MYSQL_HOST
    MYSQL_HOST=${MYSQL_HOST:-localhost}
    
    # Crear archivo de configuración temporal
    cat > .env.temp << EOF
# Configuración de MySQL para Chat en Tiempo Real
DB_HOST=$MYSQL_HOST
DB_USER=$MYSQL_USER
DB_PASSWORD=$MYSQL_PASSWORD
DB_NAME=$MYSQL_DATABASE

# Configuración del servidor WebSocket
PORT=3000
JWT_SECRET=$(openssl rand -base64 32)

# Entorno
NODE_ENV=development
EOF
    
    print_message "Archivo de configuración creado: .env.temp"
    
    # Ejecutar scripts SQL
    print_info "Ejecutando scripts SQL..."
    
    if [ -f "app/Database/mensajeria.sql" ]; then
        print_info "Ejecutando mensajeria.sql..."
        mysql -h "$MYSQL_HOST" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < app/Database/mensajeria.sql
    fi
    
    print_message "Base de datos configurada correctamente"
}

# Actualizar configuración del servidor WebSocket
update_socket_config() {
    print_info "Actualizando configuración del servidor WebSocket..."
    
    # Leer configuración del archivo .env.temp
    if [ -f ".env.temp" ]; then
        source .env.temp
        
        # Actualizar socket-server.js con la configuración
        sed -i.bak "s/host: 'localhost'/host: '$DB_HOST'/" socket-server.js
        sed -i.bak "s/user: 'root'/user: '$DB_USER'/" socket-server.js
        sed -i.bak "s/password: ''/password: '$DB_PASSWORD'/" socket-server.js
        sed -i.bak "s/database: 'appishume'/database: '$DB_NAME'/" socket-server.js
        
        print_message "Configuración del servidor WebSocket actualizada"
    else
        print_warning "Archivo .env.temp no encontrado"
    fi
}

# Crear script de inicio
create_start_script() {
    print_info "Creando script de inicio..."
    
    cat > start-chat.sh << 'EOF'
#!/bin/bash

# Script de inicio para Chat en Tiempo Real - ISHUME

echo "🚀 Iniciando Chat en Tiempo Real - ISHUME"
echo "=========================================="

# Verificar si Node.js está instalado
if ! command -v node &> /dev/null; then
    echo "❌ Node.js no está instalado"
    exit 1
fi

# Verificar si el archivo package.json existe
if [ ! -f "package.json" ]; then
    echo "❌ package.json no encontrado"
    exit 1
fi

# Verificar si las dependencias están instaladas
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias..."
    npm install
fi

# Iniciar servidor
echo "🌐 Iniciando servidor WebSocket en puerto 3000..."
echo "📡 Socket.IO disponible en: http://localhost:3000"
echo "💬 Chat disponible en: http://tu-dominio/chat"
echo ""
echo "Presiona Ctrl+C para detener el servidor"
echo ""

# Iniciar con nodemon si está disponible, sino con node
if command -v nodemon &> /dev/null; then
    nodemon socket-server.js
else
    node socket-server.js
fi
EOF
    
    chmod +x start-chat.sh
    print_message "Script de inicio creado: start-chat.sh"
}

# Crear script de producción
create_production_script() {
    print_info "Creando script de producción..."
    
    cat > start-chat-prod.sh << 'EOF'
#!/bin/bash

# Script de inicio para Chat en Tiempo Real - ISHUME (Producción)

echo "🚀 Iniciando Chat en Tiempo Real - ISHUME (Producción)"
echo "====================================================="

# Verificar si PM2 está instalado
if ! command -v pm2 &> /dev/null; then
    echo "📦 Instalando PM2..."
    npm install -g pm2
fi

# Verificar si el archivo package.json existe
if [ ! -f "package.json" ]; then
    echo "❌ package.json no encontrado"
    exit 1
fi

# Verificar si las dependencias están instaladas
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias..."
    npm install --production
fi

# Configurar variables de entorno para producción
export NODE_ENV=production

# Iniciar con PM2
echo "🌐 Iniciando servidor WebSocket con PM2..."
pm2 start socket-server.js --name "ishume-chat" --env production

echo "✅ Servidor iniciado con PM2"
echo "📊 Para ver logs: pm2 logs ishume-chat"
echo "🔄 Para reiniciar: pm2 restart ishume-chat"
echo "🛑 Para detener: pm2 stop ishume-chat"
EOF
    
    chmod +x start-chat-prod.sh
    print_message "Script de producción creado: start-chat-prod.sh"
}

# Función principal
main() {
    echo ""
    print_info "Iniciando proceso de instalación..."
    echo ""
    
    # Verificaciones previas
    if ! check_nodejs; then
        exit 1
    fi
    
    if ! check_npm; then
        exit 1
    fi
    
    check_mysql
    
    echo ""
    print_info "Procediendo con la instalación..."
    echo ""
    
    # Instalar dependencias
    if ! install_dependencies; then
        exit 1
    fi
    
    # Configurar base de datos
    setup_database
    
    # Actualizar configuración
    update_socket_config
    
    # Crear scripts
    create_start_script
    create_production_script
    
    echo ""
    print_message "¡Instalación completada exitosamente!"
    echo ""
    print_info "Próximos pasos:"
    echo "1. Ejecutar: ./start-chat.sh (para desarrollo)"
    echo "2. Ejecutar: ./start-chat-prod.sh (para producción)"
    echo "3. Acceder a: http://tu-dominio/chat"
    echo ""
    print_warning "Recuerda:"
    echo "- El servidor WebSocket debe estar ejecutándose"
    echo "- La base de datos debe estar configurada"
    echo "- Los usuarios deben estar autenticados"
    echo ""
    print_info "Para más información, consulta: CHAT_REALTIME_README.md"
    echo ""
}

# Ejecutar función principal
main "$@"
