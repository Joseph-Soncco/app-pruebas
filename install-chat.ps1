# =============================================
# SCRIPT DE INSTALACIÓN - CHAT EN TIEMPO REAL
# Sistema ISHUME - PowerShell
# =============================================

Write-Host "🚀 Instalando Sistema de Chat en Tiempo Real - ISHUME" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green

# Función para imprimir mensajes con color
function Write-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "⚠️  $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ️  $Message" -ForegroundColor Blue
}

# Verificar si Node.js está instalado
function Test-NodeJS {
    try {
        $nodeVersion = node --version
        Write-Success "Node.js encontrado: $nodeVersion"
        return $true
    }
    catch {
        Write-Error "Node.js no está instalado"
        Write-Info "Por favor instala Node.js desde: https://nodejs.org/"
        return $false
    }
}

# Verificar si npm está instalado
function Test-NPM {
    try {
        $npmVersion = npm --version
        Write-Success "npm encontrado: $npmVersion"
        return $true
    }
    catch {
        Write-Error "npm no está instalado"
        return $false
    }
}

# Verificar si MySQL está disponible
function Test-MySQL {
    try {
        mysql --version | Out-Null
        Write-Success "MySQL encontrado"
        return $true
    }
    catch {
        Write-Warning "MySQL no encontrado en PATH"
        Write-Info "Asegúrate de que MySQL esté instalado y funcionando"
        return $false
    }
}

# Instalar dependencias de Node.js
function Install-Dependencies {
    Write-Info "Instalando dependencias de Node.js..."
    
    try {
        npm install
        Write-Success "Dependencias instaladas correctamente"
        return $true
    }
    catch {
        Write-Error "Error instalando dependencias"
        return $false
    }
}

# Configurar base de datos
function Setup-Database {
    Write-Info "Configurando base de datos..."
    
    # Solicitar credenciales de MySQL
    $mysqlUser = Read-Host "Ingresa el usuario de MySQL (por defecto: root)"
    if ([string]::IsNullOrEmpty($mysqlUser)) { $mysqlUser = "root" }
    
    $mysqlPassword = Read-Host "Ingresa la contraseña de MySQL" -AsSecureString
    $mysqlPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($mysqlPassword))
    
    $mysqlDatabase = Read-Host "Ingresa el nombre de la base de datos (por defecto: appishume)"
    if ([string]::IsNullOrEmpty($mysqlDatabase)) { $mysqlDatabase = "appishume" }
    
    $mysqlHost = Read-Host "Ingresa el host de MySQL (por defecto: localhost)"
    if ([string]::IsNullOrEmpty($mysqlHost)) { $mysqlHost = "localhost" }
    
    # Generar JWT secret
    $jwtSecret = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes([System.Guid]::NewGuid().ToString()))
    
    # Crear archivo de configuración
    $envContent = @"
# Configuración de MySQL para Chat en Tiempo Real
DB_HOST=$mysqlHost
DB_USER=$mysqlUser
DB_PASSWORD=$mysqlPasswordPlain
DB_NAME=$mysqlDatabase

# Configuración del servidor WebSocket
PORT=3000
JWT_SECRET=$jwtSecret

# Entorno
NODE_ENV=development
"@
    
    $envContent | Out-File -FilePath ".env.temp" -Encoding UTF8
    Write-Success "Archivo de configuración creado: .env.temp"
    
    # Ejecutar scripts SQL
    Write-Info "Ejecutando scripts SQL..."
    
    if (Test-Path "app/Database/mensajeria.sql") {
        Write-Info "Ejecutando mensajeria.sql..."
        $sqlCommand = "mysql -h $mysqlHost -u $mysqlUser -p$mysqlPasswordPlain $mysqlDatabase < app/Database/mensajeria.sql"
        Invoke-Expression $sqlCommand
    }
    
    Write-Success "Base de datos configurada correctamente"
}

# Actualizar configuración del servidor WebSocket
function Update-SocketConfig {
    Write-Info "Actualizando configuración del servidor WebSocket..."
    
    if (Test-Path ".env.temp") {
        # Leer configuración del archivo .env.temp
        $envContent = Get-Content ".env.temp"
        $envVars = @{}
        
        foreach ($line in $envContent) {
            if ($line -match "^([^=]+)=(.*)$") {
                $envVars[$matches[1]] = $matches[2]
            }
        }
        
        # Actualizar socket-server.js
        $socketContent = Get-Content "socket-server.js" -Raw
        
        $socketContent = $socketContent -replace "host: 'localhost'", "host: '$($envVars.DB_HOST)'"
        $socketContent = $socketContent -replace "user: 'root'", "user: '$($envVars.DB_USER)'"
        $socketContent = $socketContent -replace "password: ''", "password: '$($envVars.DB_PASSWORD)'"
        $socketContent = $socketContent -replace "database: 'appishume'", "database: '$($envVars.DB_NAME)'"
        
        $socketContent | Out-File -FilePath "socket-server.js" -Encoding UTF8
        Write-Success "Configuración del servidor WebSocket actualizada"
    }
    else {
        Write-Warning "Archivo .env.temp no encontrado"
    }
}

# Crear script de inicio
function Create-StartScript {
    Write-Info "Creando script de inicio..."
    
    $startScriptContent = @'
# Script de inicio para Chat en Tiempo Real - ISHUME

Write-Host "🚀 Iniciando Mensajería en Tiempo Real - ISHUME" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green

# Verificar si Node.js está instalado
try {
    node --version | Out-Null
}
catch {
    Write-Host "❌ Node.js no está instalado" -ForegroundColor Red
    exit 1
}

# Verificar si el archivo package.json existe
if (-not (Test-Path "package.json")) {
    Write-Host "❌ package.json no encontrado" -ForegroundColor Red
    exit 1
}

# Verificar si las dependencias están instaladas
if (-not (Test-Path "node_modules")) {
    Write-Host "📦 Instalando dependencias..." -ForegroundColor Blue
    npm install
}

# Iniciar servidor
Write-Host "🌐 Iniciando servidor WebSocket en puerto 3000..." -ForegroundColor Blue
Write-Host "📡 Socket.IO disponible en: http://localhost:3000" -ForegroundColor Blue
Write-Host "💬 Mensajería disponible en: http://tu-dominio/mensajeria" -ForegroundColor Blue
Write-Host ""
Write-Host "Presiona Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host ""

# Iniciar con nodemon si está disponible, sino con node
try {
    nodemon socket-server.js
}
catch {
    node socket-server.js
}
'@
    
    $startScriptContent | Out-File -FilePath "start-chat.ps1" -Encoding UTF8
    Write-Success "Script de inicio creado: start-chat.ps1"
}

# Crear script de producción
function Create-ProductionScript {
    Write-Info "Creando script de producción..."
    
    $prodScriptContent = @'
# Script de inicio para Chat en Tiempo Real - ISHUME (Producción)

Write-Host "🚀 Iniciando Chat en Tiempo Real - ISHUME (Producción)" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green

# Verificar si PM2 está instalado
try {
    pm2 --version | Out-Null
}
catch {
    Write-Host "📦 Instalando PM2..." -ForegroundColor Blue
    npm install -g pm2
}

# Verificar si el archivo package.json existe
if (-not (Test-Path "package.json")) {
    Write-Host "❌ package.json no encontrado" -ForegroundColor Red
    exit 1
}

# Verificar si las dependencias están instaladas
if (-not (Test-Path "node_modules")) {
    Write-Host "📦 Instalando dependencias..." -ForegroundColor Blue
    npm install --production
}

# Configurar variables de entorno para producción
$env:NODE_ENV = "production"

# Iniciar con PM2
Write-Host "🌐 Iniciando servidor WebSocket con PM2..." -ForegroundColor Blue
pm2 start socket-server.js --name "ishume-chat" --env production

Write-Host "✅ Servidor iniciado con PM2" -ForegroundColor Green
Write-Host "📊 Para ver logs: pm2 logs ishume-chat" -ForegroundColor Blue
Write-Host "🔄 Para reiniciar: pm2 restart ishume-chat" -ForegroundColor Blue
Write-Host "🛑 Para detener: pm2 stop ishume-chat" -ForegroundColor Blue
'@
    
    $prodScriptContent | Out-File -FilePath "start-chat-prod.ps1" -Encoding UTF8
    Write-Success "Script de producción creado: start-chat-prod.ps1"
}

# Función principal
function Main {
    Write-Host ""
    Write-Info "Iniciando proceso de instalación..."
    Write-Host ""
    
    # Verificaciones previas
    if (-not (Test-NodeJS)) {
        exit 1
    }
    
    if (-not (Test-NPM)) {
        exit 1
    }
    
    Test-MySQL
    
    Write-Host ""
    Write-Info "Procediendo con la instalación..."
    Write-Host ""
    
    # Instalar dependencias
    if (-not (Install-Dependencies)) {
        exit 1
    }
    
    # Configurar base de datos
    Setup-Database
    
    # Actualizar configuración
    Update-SocketConfig
    
    # Crear scripts
    Create-StartScript
    Create-ProductionScript
    
    Write-Host ""
    Write-Success "¡Instalación completada exitosamente!"
    Write-Host ""
    Write-Info "Próximos pasos:"
    Write-Host "1. Ejecutar: .\start-chat.ps1 (para desarrollo)"
    Write-Host "2. Ejecutar: .\start-chat-prod.ps1 (para producción)"
    Write-Host "3. Acceder a: http://tu-dominio/chat"
    Write-Host ""
    Write-Warning "Recuerda:"
    Write-Host "- El servidor WebSocket debe estar ejecutándose"
    Write-Host "- La base de datos debe estar configurada"
    Write-Host "- Los usuarios deben estar autenticados"
    Write-Host ""
    Write-Info "Para más información, consulta: CHAT_REALTIME_README.md"
    Write-Host ""
}

# Ejecutar función principal
Main
