/**
 * JavaScript para Panel Estudiantil
 * Siguiendo estándares UNA/ESCINF - Proyecto 2025-07
 */

class PanelEstudiantil {
    constructor() {
        this.animationDelay = 100;
        this.isLoading = false;
        
        this.initializeAnimations();
        this.initializeInteractions();
        this.initializeNotifications();
        this.loadDynamicContent();
    }
    
    /**
     * Inicializar animaciones de entrada
     */
    initializeAnimations() {
        // Animar elementos principales con delay escalonado
        this.animateElements('.quick-action-card', 'slide-up', 200);
        this.animateElements('.dashboard-card', 'fade-in', 400);
    }
    
    /**
     * Animar elementos con delay
     */
    animateElements(selector, animationClass, baseDelay = 0) {
        const elements = document.querySelectorAll(selector);
        
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
                element.classList.add(animationClass);
            }, baseDelay + (index * this.animationDelay));
        });
    }
    
    /**
     * Inicializar interacciones y efectos hover
     */
    initializeInteractions() {
        this.setupHoverEffects();
        this.setupClickAnalytics();
        this.setupTooltips();
        this.setupLazyLoading();
    }
    
    /**
     * Configurar efectos hover avanzados
     */
    setupHoverEffects() {
        // Efecto ripple en acciones rápidas
        const quickActions = document.querySelectorAll('.quick-action-card');
        quickActions.forEach(card => {
            card.addEventListener('click', (e) => {
                this.createRippleEffect(e, card);
            });
        });
    }
    
    /**
     * Crear efecto ripple
     */
    createRippleEffect(event, element) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            background-color: rgba(3, 73, 145, 0.3);
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
    
    /**
     * Configurar analytics de clicks
     */
    setupClickAnalytics() {
        // Rastrear clicks en acciones rápidas
        const quickActions = document.querySelectorAll('.quick-action-card');
        quickActions.forEach(card => {
            card.addEventListener('click', (e) => {
                const actionType = card.getAttribute('id') || 'unknown';
                this.trackUserAction('quick_action_click', actionType);
            });
        });
        
        // Rastrear navegación
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const linkId = link.getAttribute('id') || 'unknown';
                this.trackUserAction('navigation_click', linkId);
            });
        });
    }
    
    /**
     * Rastrear acciones del usuario (para futura implementación de analytics)
     */
    trackUserAction(action, details) {
        // Placeholder para Google Analytics o sistema propio
        console.log(`Usuario acción: ${action} - ${details}`);
        
        // En producción, aquí iría la llamada a GA4 o similar
        // gtag('event', action, { 'custom_parameter': details });
    }
    
    /**
     * Configurar tooltips informativos
     */
    setupTooltips() {
        const badges = document.querySelectorAll('.badge-una');
        badges.forEach(badge => {
            const statusText = badge.textContent.trim();
            let tooltipText = '';
            
            switch(statusText) {
                case 'Pendiente de Revision':
                    tooltipText = 'Su propuesta está siendo evaluada por el comité académico';
                    break;
                case 'Aprobado':
                    tooltipText = 'Su propuesta ha sido aprobada oficialmente';
                    break;
                case 'Rechazado':
                    tooltipText = 'Su propuesta requiere modificaciones antes de ser aprobada';
                    break;
                case 'Registrado':
                    tooltipText = 'El proyecto grupal ha sido creado y está activo';
                    break;
                case 'En Desarrollo':
                    tooltipText = 'El proyecto se encuentra en fase de desarrollo';
                    break;
            }
            
            if (tooltipText) {
                badge.setAttribute('title', tooltipText);
                badge.style.cursor = 'help';
            }
        });
    }
    
    /**
     * Configurar carga lazy para contenido dinámico
     */
    setupLazyLoading() {
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadSectionContent(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        const lazyElements = document.querySelectorAll('.dashboard-card');
        lazyElements.forEach(element => {
            observer.observe(element);
        });
    }
    
    /**
     * Cargar contenido de sección específica
     */
    loadSectionContent(element) {
        // Simular carga de contenido adicional
        const loadingIndicator = element.querySelector('.loading-skeleton');
        if (loadingIndicator) {
            setTimeout(() => {
                loadingIndicator.style.display = 'none';
                element.querySelector('.actual-content').style.display = 'block';
            }, 500);
        }
    }
    
    /**
     * Inicializar sistema de notificaciones
     */
    initializeNotifications() {
        this.checkForUpdates();
        this.showWelcomeMessage();
    }
    
    /**
     * Verificar actualizaciones desde el servidor
     */
    async checkForUpdates() {
        // En desarrollo, simular check
        setTimeout(() => {
            const lastUpdate = localStorage.getItem('lastPanelUpdate');
            const now = new Date().getTime();
            
            if (!lastUpdate || (now - parseInt(lastUpdate)) > 24 * 60 * 60 * 1000) {
                this.showUpdateNotification();
                localStorage.setItem('lastPanelUpdate', now.toString());
            }
        }, 3000);
    }
    
    /**
     * Mostrar notificación de bienvenida
     */
    showWelcomeMessage() {
        const isFirstVisit = !localStorage.getItem('panelVisited');
        
        if (isFirstVisit) {
            setTimeout(() => {
                this.showNotification('info', 'Bienvenido al Sistema de Gestión de TFG', 'Explore las opciones disponibles para gestionar sus propuestas y proyectos grupales.');
                localStorage.setItem('panelVisited', 'true');
            }, 2000);
        }
    }
    
    /**
     * Mostrar notificación de actualización
     */
    showUpdateNotification() {
        this.showNotification('info', 'Nuevas funcionalidades disponibles', 'Se han agregado mejoras al sistema de gestión de proyectos grupales.');
    }
    
    /**
     * Sistema de notificaciones personalizado
     */
    showNotification(type, title, message, duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const iconClass = type === 'info' ? 'bi-info-circle' : 'bi-check-circle';
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="bi ${iconClass}"></i>
                <div>
                    <strong>${title}</strong>
                    <p>${message}</p>
                </div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-left: 4px solid #034991;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 1rem;
            max-width: 400px;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-remover
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, duration);
    }
    
    /**
     * Cargar contenido dinámico adicional
     */
    async loadDynamicContent() {
        // Cargar actividad reciente adicional si es necesario
        this.loadRecentActivity();
    }

    /**
     * Cargar actividad reciente adicional
     */
    async loadRecentActivity() {
        // En producción, esto sería una llamada AJAX
        setTimeout(() => {
            const activityCards = document.querySelectorAll('.dashboard-card');
            activityCards.forEach(card => {
                this.addShimmerEffect(card);
            });
        }, 1000);
    }
    
    /**
     * Agregar efecto shimmer durante carga
     */
    addShimmerEffect(element) {
        element.style.background = 'linear-gradient(90deg, #f0f0f0 25%, #e0e0f0 50%, #f0f0f0 75%)';
        element.style.backgroundSize = '200% 100%';
        element.style.animation = 'loading 1.5s infinite';
        
        setTimeout(() => {
            element.style.background = 'white';
            element.style.animation = '';
        }, 2000);
    }
    
    /**
     * Método para refrescar datos del panel
     */
    refreshPanelData() {
        this.isLoading = true;
        this.showNotification('info', 'Actualizando...', 'Cargando los datos más recientes.');
        
        // Simular carga
        setTimeout(() => {
            this.isLoading = false;
            this.showNotification('success', 'Actualizado', 'Los datos han sido actualizados correctamente.');
            location.reload();
        }, 2000);
    }
    
    /**
     * Métodos de utilidad
     */
    formatDate(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('es-CR', options);
    }
    
    truncateText(text, maxLength = 100) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    /**
     * Manejo de errores
     */
    handleError(error, context = 'panel') {
        console.error(`Error en ${context}:`, error);
        this.showNotification('error', 'Error', 'Ha ocurrido un error. Por favor, recargue la página.');
    }
}

// Agregar estilos dinámicos para animaciones
const dynamicStyles = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .notification-content i {
        font-size: 1.25rem;
        color: #034991;
        margin-top: 2px;
    }
    
    .notification-content strong {
        display: block;
        margin-bottom: 4px;
        color: #2c3e50;
    }
    
    .notification-content p {
        margin: 0;
        font-size: 0.9rem;
        color: #6c757d;
        line-height: 1.4;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: #adb5bd;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        margin-left: auto;
    }
    
    .notification-close:hover {
        color: #6c757d;
    }
    
    .loading-skeleton {
        height: 20px;
        margin-bottom: 10px;
        border-radius: 4px;
    }
`;

// Inyectar estilos dinámicos
const styleSheet = document.createElement('style');
styleSheet.textContent = dynamicStyles;
document.head.appendChild(styleSheet);

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.panelEstudiantil = new PanelEstudiantil();
    
    // Exponer métodos útiles globalmente
    window.refreshPanel = () => window.panelEstudiantil.refreshPanelData();
});

// Manejo de errores globales
window.addEventListener('error', function(event) {
    if (window.panelEstudiantil) {
        window.panelEstudiantil.handleError(event.error, 'global');
    }
});

// Manejar cambios de visibilidad de la página
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.panelEstudiantil) {
        // Usuario regresó a la pestaña, verificar actualizaciones
        window.panelEstudiantil.checkForUpdates();
    }
});