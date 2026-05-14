# Proyecto
Código del proyecto
Sistema de gestión de tareas de mantenimiento con PHP y Microsoft Graph API

# Sistema de Gestión de Mantenimiento Proactivo - 720tec

Este proyecto es una aplicación web desarrollada para automatizar y centralizar el registro de tareas de mantenimiento proactivo en **720tec**. La solución integra un frontend moderno con el ecosistema de **Microsoft 365** mediante el uso de **Microsoft Graph API** y **SharePoint Online**.

## 🚀 Características

- **Registro Centralizado:** Interfaz web para técnicos que vuelca los datos directamente en una lista de SharePoint.
- **Formulario Dinámico:** Las opciones de Prioridad, Impacto y Periodicidad se recuperan en tiempo real desde las columnas de SharePoint.
- **Autenticación OAuth 2.0:** Gestión segura de tokens mediante el flujo de credenciales de cliente en Azure AD (Entra ID).
- **Cálculo Automático:** El sistema calcula las fechas de vencimiento basándose en la periodicidad seleccionada (Semanal, Mensual, etc.).
- **Diseño Responsive:** Construido con Bootstrap 5 para su uso en dispositivos móviles y escritorio.

## 🛠️ Stack Tecnológico

- **Backend:** PHP 8.x (cURL para peticiones a Graph API).
- **Frontend:** HTML5, JavaScript (Vanilla), Bootstrap 5.
- **Integración:** Microsoft Graph API v1.0.
- **Base de Datos:** SharePoint Online List.

## 📋 Requisitos e Instalación

1.  **Azure App Registration:** Es necesario registrar una aplicación en el portal de Azure con permisos `Sites.ReadWrite.All` y `User.Read.All` (con consentimiento del administrador).
2.  **Configuración del servidor:** Servidor web con PHP y extensión `php-curl` habilitada.
3.  **Variables de entorno:** Configurar los IDs en el archivo `config.php`.

## ⚙️ Configuración (config.php)

Para el funcionamiento del sistema, se deben definir las siguientes constantes:

```php
define('TENANT_ID', 'tu-id-de-inquilino');
define('CLIENT_ID', 'tu-id-de-aplicación');
define('CLIENT_SECRET', 'tu-clave-secreta');
define('SITE_ID', 'tu-id-de-sitio-sharepoint');
define('LIST_ID', 'tu-id-de-la-lista');
