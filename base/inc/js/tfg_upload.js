class TfgUploadManager {
    constructor() {
        this.selectedMembers = [];
        this.maxMembers = 1;
        this.searchTimeout = null;
        this.baseUrl = this.getBaseUrl();
        
        this.initializeEventListeners();
        this.loadInitialMaxMembers();
        this.updateMembersDisplay();
    }
    
    getBaseUrl() {
        const scripts = document.getElementsByTagName('script');
        for (let script of scripts) {
            if (script.src && script.src.includes('tfg_upload.js')) {
                const scriptPath = script.src;
                const basePath = scriptPath.substring(0, scriptPath.indexOf('/inc/js/'));
                return basePath;
            }
        }
        return '/SGPFL/base';
    }
    
    loadInitialMaxMembers() {
        const projectTypeSelect = document.getElementById('sel-project-type');
        if (projectTypeSelect && projectTypeSelect.selectedIndex >= 0) {
            const selectedOption = projectTypeSelect.options[projectTypeSelect.selectedIndex];
            this.maxMembers = parseInt(selectedOption.getAttribute('data-max-members')) || 1;
            console.log('Max members inicializado:', this.maxMembers);
        }
    }
    
    initializeEventListeners() {
        const projectTypeSelect = document.getElementById('sel-project-type');
        const searchInput = document.getElementById('inp-search-members');
        const searchButton = document.getElementById('btn-search-members');
        const titleInput = document.getElementById('inp-title');
        const descriptionTextarea = document.getElementById('txt-project-description');
        const documentInput = document.getElementById('inp-document');
        const form = document.getElementById('tfgGroupForm');
        
        // Debug: Verificar que se encuentren los elementos
        console.log('Elementos encontrados:', {
            projectTypeSelect: !!projectTypeSelect,
            searchInput: !!searchInput,
            searchButton: !!searchButton,
            titleInput: !!titleInput,
            descriptionTextarea: !!descriptionTextarea,
            documentInput: !!documentInput,
            form: !!form
        });
        
        // Eventos principales
        if (projectTypeSelect) {
            projectTypeSelect.addEventListener('change', (e) => this.handleProjectTypeChange(e));
        }
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearchInput(e));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchUsers(e.target.value.trim());
                }
            });
        }
        
        if (searchButton) {
            searchButton.addEventListener('click', () => this.searchUsers(searchInput.value.trim()));
        }
        
        if (titleInput) {
            titleInput.addEventListener('blur', (e) => this.validateTitle(e.target.value));
        }
        
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', (e) => this.updateCharacterCounter(e.target));
        }
        
        // El manejo de archivos ahora se hace en el código inline del PHP
        // para soportar acumulación de archivos con botón de eliminar
        // if (documentInput) {
        //     documentInput.addEventListener('change', (e) => this.handleFileSelection(e));
        // }
        
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        } else {
            console.error('Formulario #tfgGroupForm no encontrado');
        }
    }
    
    handleProjectTypeChange(event) {
        const selectedOption = event.target.options[event.target.selectedIndex];
        this.maxMembers = parseInt(selectedOption.getAttribute('data-max-members')) || 1;
        
        console.log('Tipo de proyecto cambiado. Máximo miembros:', this.maxMembers);
        
        this.updateMembersDisplay();
        
        if (this.selectedMembers.length >= this.maxMembers) {
            this.selectedMembers = this.selectedMembers.slice(0, this.maxMembers - 1);
            this.updateMembersDisplay();
            this.showAlert('warning', `Se removieron miembros excedentes. Máximo ${this.maxMembers} miembros para este tipo.`);
        }
    }
    
    handleSearchInput(event) {
        const searchTerm = event.target.value.trim();
        
        // No permitir búsqueda si está deshabilitado
        if (event.target.disabled) {
            return;
        }
        
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        this.searchTimeout = setTimeout(() => {
            if (searchTerm.length >= 2) {
                this.searchUsers(searchTerm);
            } else {
                this.hideSearchResults();
            }
        }, 300);
    }
    
    async searchUsers(searchTerm) {
        // No permitir búsqueda si se alcanzó el máximo
        const totalMembers = this.selectedMembers.length + 1;
        if (totalMembers >= this.maxMembers) {
            this.hideSearchResults();
            return;
        }
        
        if (searchTerm.length < 2) {
            this.hideSearchResults();
            return;
        }
        
        try {
            // CONSULTA REAL A LA BASE DE DATOS
            const response = await fetch(`search_users.php?term=${encodeURIComponent(searchTerm)}`);
            
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            
            const users = await response.json();
            this.displaySearchResults(users);
            
        } catch (error) {
            console.error('Error al buscar usuarios:', error);
            
            // Fallback con datos de prueba solo para desarrollo
            const mockResults = [
                {id: '112170041', nombre: 'Usuario de Prueba 1', email: 'usuario1@est.una.ac.cr'},
                {id: '112170042', nombre: 'Usuario de Prueba 2', email: 'usuario2@est.una.ac.cr'}
            ];
            
            const filteredResults = mockResults.filter(user => 
                user.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.id.includes(searchTerm)
            );
            
            this.displaySearchResults(filteredResults);
            this.showAlert('warning', 'Usando datos de prueba. Error: ' + error.message);
        }
    }
    
    displaySearchResults(users) {
        const resultsContainer = document.getElementById('div-search-results');
        const currentUserId = this.getCurrentUserId();
        
        if (!resultsContainer) return;
        
        if (users.length === 0) {
            resultsContainer.innerHTML = `
                <div class="alert-tfg alert-tfg-info">
                    <i class="bi bi-info-circle"></i>
                    No se encontraron estudiantes con ese criterio de búsqueda.
                </div>
            `;
        } else {
            let html = '<div class="search-results"><h6><i class="bi bi-people-fill"></i> Estudiantes encontrados:</h6>';
            
            users.forEach(user => {
                const isAlreadySelected = this.selectedMembers.some(m => m.id === user.id);
                const isCurrentUser = user.id === currentUserId;
                
                if (!isAlreadySelected && !isCurrentUser) {
                    html += `
                        <div class="search-result-item">
                            <div class="search-result-content">
                                <strong>${this.escapeHtml(user.nombre)}</strong>
                                <small class="text-muted">
                                    <i class="bi bi-envelope"></i> ${this.escapeHtml(user.email)} | 
                                    <i class="bi bi-person-badge"></i> ID: ${this.escapeHtml(user.id)}
                                </small>
                            </div>
                            <button type="button" class="btn-tfg btn-tfg-secondary btn-tfg-sm" onclick="window.tfgManager.addMember('${user.id}', '${this.escapeHtml(user.nombre)}', '${this.escapeHtml(user.email)}')">
                                <i class="bi bi-plus-circle"></i> Agregar
                            </button>
                        </div>
                    `;
                }
            });
            
            html += '</div>';
            resultsContainer.innerHTML = html;
        }
        
        resultsContainer.style.display = 'block';
    }
    
    addMember(userId, userName, userEmail) {
        if (this.selectedMembers.length >= (this.maxMembers - 1)) {
            this.showAlert('warning', `Solo se permiten ${this.maxMembers} miembros para este tipo de proyecto (incluyendo el líder).`);
            return;
        }
        
        this.selectedMembers.push({
            id: userId,
            nombre: userName,
            email: userEmail
        });
        
        this.updateMembersDisplay();
        this.hideSearchResults();
        document.getElementById('inp-search-members').value = '';
        
        this.showAlert('info', `${userName} ha sido agregado al grupo.`);
    }
    
    removeMember(userId) {
        this.selectedMembers = this.selectedMembers.filter(m => m.id !== userId);
        this.updateMembersDisplay();
        
        this.showAlert('info', 'Miembro removido del grupo.');
    }
    
    updateMembersDisplay() {
        const membersList = document.getElementById('list-selected-members');
        const currentUserId = this.getCurrentUserId();
        const searchInput = document.getElementById('inp-search-members');
        const searchButton = document.getElementById('btn-search-members');
        
        if (!membersList) return;
        
        let html = `
            <div class="member-card">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                    <i class="bi bi-star-fill text-warning" style="font-size: 1.2em;"></i>
                    <div>
                        <strong>Usted (Líder del Proyecto)</strong><br>
                        <small class="text-muted">ID: ${this.escapeHtml(currentUserId)}</small>
                    </div>
                </div>
            </div>
        `;
        
        this.selectedMembers.forEach(member => {
            html += `
                <div class="member-card">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                        <i class="bi bi-person-fill text-primary" style="font-size: 1.2em;"></i>
                        <div>
                            <strong>${this.escapeHtml(member.nombre)}</strong><br>
                            <small class="text-muted">${this.escapeHtml(member.email)} | ID: ${this.escapeHtml(member.id)}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-tfg btn-tfg-secondary btn-tfg-sm" onclick="window.tfgManager.removeMember('${member.id}')">
                        <i class="bi bi-x"></i> Remover
                    </button>
                    <input type="hidden" name="members[]" value="${this.escapeHtml(member.id)}">
                </div>
            `;
        });
        
        membersList.innerHTML = html;
        
        const totalMembers = this.selectedMembers.length + 1;
        const memberCountElement = document.getElementById('member-count');
        if (memberCountElement) {
            memberCountElement.textContent = `${totalMembers}/${this.maxMembers}`;
        }
        
        // Deshabilitar búsqueda si se alcanzó el máximo
        const isMaxReached = totalMembers >= this.maxMembers;
        console.log('Estado de búsqueda:', {
            totalMembers: totalMembers,
            maxMembers: this.maxMembers,
            isMaxReached: isMaxReached
        });
        
        if (searchInput) {
            if (isMaxReached) {
                // Deshabilitar completamente el input
                searchInput.disabled = true;
                searchInput.readOnly = true;
                searchInput.value = '';
                searchInput.placeholder = 'Límite de miembros alcanzado';
                searchInput.style.backgroundColor = '#e9ecef';
                searchInput.style.cursor = 'not-allowed';
                this.hideSearchResults();
                
                // Agregar event listener para prevenir cualquier interacción
                searchInput.addEventListener('keydown', this.preventInput);
                searchInput.addEventListener('keypress', this.preventInput);
                searchInput.addEventListener('keyup', this.preventInput);
                searchInput.addEventListener('input', this.preventInput);
            } else {
                // Habilitar el input
                searchInput.disabled = false;
                searchInput.readOnly = false;
                searchInput.placeholder = 'Buscar por nombre, email o cédula...';
                searchInput.style.backgroundColor = '';
                searchInput.style.cursor = '';
                
                // Remover event listeners de prevención
                searchInput.removeEventListener('keydown', this.preventInput);
                searchInput.removeEventListener('keypress', this.preventInput);
                searchInput.removeEventListener('keyup', this.preventInput);
                searchInput.removeEventListener('input', this.preventInput);
            }
        }
        if (searchButton) {
            searchButton.disabled = isMaxReached;
        }
    }
    
    preventInput(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
    
    hideSearchResults() {
        const resultsContainer = document.getElementById('div-search-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }
    
    validateTitle(title) {
        if (title.length < 10) {
            this.showFieldError('inp-title', 'El título debe tener al menos 10 caracteres');
            return false;
        }
        
        if (title.length > 255) {
            this.showFieldError('inp-title', 'El título no puede exceder 255 caracteres');
            return false;
        }
        
        this.clearFieldError('inp-title');
        return true;
    }
    
    updateCharacterCounter(textarea) {
        const maxLength = parseInt(textarea.getAttribute('maxlength')) || 500;
        const currentLength = textarea.value.length;
        
        let counterId = textarea.id + '-counter';
        let counter = document.getElementById(counterId);
        
        if (!counter) {
            counter = document.createElement('div');
            counter.id = counterId;
            counter.className = 'character-counter';
            textarea.parentNode.appendChild(counter);
        }
        
        counter.textContent = `${currentLength}/${maxLength}`;
        
        if (currentLength > maxLength * 0.9) {
            counter.style.color = '#e74c3c';
        } else {
            counter.style.color = '#6c757d';
        }
    }
    
    handleFileSelection(event) {
        const files = event.target.files;
        const previewContainer = document.getElementById('file-preview');
        
        if (!files || files.length === 0) {
            if (previewContainer) previewContainer.style.display = 'none';
            return;
        }
        
        const maxTotalSize = 8 * 1024 * 1024; // 8MB total
        const maxFileSize = 8 * 1024 * 1024; // 8MB por archivo
        const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        let totalSize = 0;
        
        // Validar cada archivo
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            totalSize += file.size;
            
            if (file.size > maxFileSize) {
                this.showFieldError('inp-document', `El archivo "${file.name}" supera el tamaño máximo de 8 MB`);
                event.target.value = '';
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                this.showFieldError('inp-document', `El archivo "${file.name}" no es un PDF o DOCX válido`);
                event.target.value = '';
                return;
            }
        }
        
        // Validar tamaño total
        if (totalSize > maxTotalSize) {
            this.showFieldError('inp-document', 'El tamaño total de los archivos excede 8 MB');
            event.target.value = '';
            return;
        }
        
        this.clearFieldError('inp-document');
        
        if (!previewContainer) {
            const preview = document.createElement('div');
            preview.id = 'file-preview';
            preview.className = 'file-preview';
            event.target.parentNode.appendChild(preview);
        }
        
        const preview = document.getElementById('file-preview');
        const totalSizeInMB = (totalSize / (1024 * 1024)).toFixed(2);
        
        if (files.length === 1) {
            const file = files[0];
            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            preview.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="bi bi-file-earmark-text text-primary" style="font-size: 1.5em;"></i>
                    <div>
                        <strong>${this.escapeHtml(file.name)}</strong><br>
                        <small class="text-muted">Tamaño: ${sizeInMB} MB | Tipo: ${file.type.split('/')[1].toUpperCase()}</small>
                    </div>
                </div>
            `;
        } else {
            let filesHtml = `<div style="margin-bottom: 8px;"><strong>${files.length} archivos seleccionados</strong> (Total: ${totalSizeInMB} MB)</div>`;
            filesHtml += '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                filesHtml += `
                    <div class="badge bg-secondary" style="display: flex; align-items: center; gap: 4px; padding: 6px 10px;">
                        <i class="bi bi-file-earmark"></i>
                        ${this.escapeHtml(file.name)} (${sizeInMB} MB)
                    </div>
                `;
            }
            filesHtml += '</div>';
            preview.innerHTML = filesHtml;
        }
        preview.style.display = 'block';
    }
    
    handleFormSubmit(event) {
        console.log('Formulario enviado - iniciando validación');
        event.preventDefault();
        
        if (!this.validateForm()) {
            console.log('Validación fallida');
            return false;
        }
        
        console.log('Validación exitosa - mostrando confirmación');
        
        // Mostrar confirmación
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirmar envío',
                text: '¿Está seguro de enviar la propuesta TFG y crear el grupo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#034991',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Usuario confirmó - enviando formulario');
                    this.submitForm();
                }
            });
        } else {
            if (confirm('¿Está seguro de enviar la propuesta TFG y crear el grupo?')) {
                console.log('Usuario confirmó (confirm nativo) - enviando formulario');
                this.submitForm();
            }
        }
        
        return false;
    }
    
    submitForm() {
        const form = document.getElementById('tfgGroupForm');
        
        if (!form) {
            console.error('Formulario no encontrado');
            this.showAlert('warning', 'Error: Formulario no encontrado');
            return;
        }
        
        console.log('Enviando formulario...');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Procesando...',
                text: 'Enviando propuesta y creando grupo',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        // Agregar miembros como campos hidden antes de enviar
        this.addMembersToForm(form);
        
        form.submit();
    }
    
    addMembersToForm(form) {
        // Remover campos hidden anteriores de miembros
        const existingMemberInputs = form.querySelectorAll('input[name="members[]"]');
        existingMemberInputs.forEach(input => input.remove());
        
        // Agregar nuevos campos hidden para cada miembro
        this.selectedMembers.forEach(member => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'members[]';
            input.value = member.id;
            form.appendChild(input);
        });
        
        console.log('Miembros agregados al formulario:', this.selectedMembers.length);
    }
    
    validateForm() {
        const title = document.getElementById('inp-title').value.trim();
        const disciplinesElement = document.getElementById('inp-disciplines');
        const disciplines = disciplinesElement ? disciplinesElement.value.trim() : ''; // Opcional
        const projectType = document.getElementById('sel-project-type').value;
        const projectDescription = document.getElementById('txt-project-description').value.trim();
        // ===== INICIO DE CAMBIOS SOLICITADOS =====
        const documentFile = document.getElementById('inp-document').files[0];
        // ===== FIN DE CAMBIOS SOLICITADOS =====
        const acceptTerms = document.getElementById('chk-terms').checked;
        
        let errors = [];
        
        console.log('Validando formulario:', {
            title: title.length,
            disciplines: disciplines.length,
            projectType: !!projectType,
            projectDescription: projectDescription.length,
            // ===== INICIO DE CAMBIOS SOLICITADOS =====
            documentFile: !!documentFile,
            // ===== FIN DE CAMBIOS SOLICITADOS =====
            acceptTerms: acceptTerms
        });
        
        if (!title || !this.validateTitle(title)) {
            errors.push("El título debe ser válido (10-255 caracteres)");
        }
        
        // Disciplinas es opcional - validación removida
        
        if (!projectType) {
            errors.push("Debe seleccionar un tipo de proyecto");
        }
        
        if (!projectDescription || projectDescription.length < 20) {
            errors.push("La descripción del proyecto debe tener al menos 20 caracteres");
        }
        
        // ===== INICIO DE CAMBIOS SOLICITADOS =====
        if (!documentFile) {
            errors.push("Debe subir el documento de la propuesta");
        }
        // ===== FIN DE CAMBIOS SOLICITADOS =====
        
        if (!acceptTerms) {
            errors.push("Debe aceptar los términos y condiciones");
        }
        
        if (errors.length > 0) {
            console.log('Errores de validación:', errors);
            this.showAlert('warning', 'Errores en el formulario:<br>• ' + errors.join('<br>• '));
            return false;
        }
        
        return true;
    }
    
    showAlert(type, message) {
        const existingAlerts = document.querySelectorAll('.alert-tfg-temp');
        existingAlerts.forEach(alert => alert.remove());
        
        const alertClass = `alert-tfg alert-tfg-${type} alert-tfg-temp`;
        const icon = type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
        const alert = document.createElement('div');
        alert.className = alertClass;
        alert.innerHTML = `
            <i class="bi bi-${icon}"></i>
            <div>${message}</div>
        `;
        
        const form = document.getElementById('tfgGroupForm');
        if (form) {
            form.insertBefore(alert, form.firstChild);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    }
    
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.style.borderColor = '#e74c3c';
        
        let errorElement = document.getElementById(fieldId + '-error');
        if (!errorElement) {
            errorElement = document.createElement('small');
            errorElement.id = fieldId + '-error';
            errorElement.style.color = '#e74c3c';
            errorElement.style.display = 'block';
            errorElement.style.marginTop = '4px';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }
    
    clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        if (field) {
            field.style.borderColor = '#e1e8ed';
        }
        
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    getCurrentUserId() {
        // Obtener el ID del usuario actual desde la variable global definida en PHP
        return window.CURRENT_USER_ID || '112170040';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - inicializando TfgUploadManager');
    window.tfgManager = new TfgUploadManager();
});