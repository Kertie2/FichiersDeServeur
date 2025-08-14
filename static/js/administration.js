document.addEventListener('DOMContentLoaded', () => {
    // Éléments de la page d'administration
    const procedureTypesListDiv = document.getElementById('procedureTypesList');
    const adminMessageDiv = document.getElementById('adminMessage');
    const openLogsModalButton = document.getElementById('openLogsModalButton'); // Bouton pour ouvrir la modale des logs

    // Éléments de la modale de gestion des champs
    const manageFieldsModal = document.getElementById('manageFieldsModal');
    const closeManageFieldsModalButton = document.getElementById('closeManageFieldsModalButton');
    const manageFieldsModalTitle = document.getElementById('manageFieldsModalTitle');
    const currentProcedureTypeNameSpan = document.getElementById('currentProcedureTypeName');
    const fieldsListContainer = document.getElementById('fieldsListContainer');
    const addFieldButton = document.getElementById('addFieldButton');
    const fieldManageMessageDiv = document.getElementById('fieldManageMessage');

    // Éléments de la modale d'ajout/édition de champ
    const fieldEditModal = document.getElementById('fieldEditModal');
    const closeFieldEditModalButton = document.getElementById('closeFieldEditModalButton');
    const fieldEditModalTitle = document.getElementById('fieldEditModalTitle');
    const fieldEditForm = document.getElementById('fieldEditForm');
    const editFieldProcedureTypeId = document.getElementById('editFieldProcedureTypeId');
    const editFieldId = document.getElementById('editFieldId');
    const editFieldName = document.getElementById('editFieldName');
    const editFieldLabel = document.getElementById('editFieldLabel');
    const editFieldType = document.getElementById('editFieldType');
    const editFieldRequired = document.getElementById('editFieldRequired');
    const optionsGroup = document.getElementById('optionsGroup');
    const editFieldOptions = document.getElementById('editFieldOptions');
    const editFieldOrder = document.getElementById('editFieldOrder');
    const saveFieldButton = document.getElementById('saveFieldButton');
    const fieldEditMessageDiv = document.getElementById('fieldEditMessage');

    // Nouveaux éléments de la modale des logs
    const logsModal = document.getElementById('logsModal');
    const closeLogsModalButton = document.getElementById('closeLogsModalButton');
    const logTypeSelect = document.getElementById('logTypeSelect');
    const logLinesInput = document.getElementById('logLinesInput');
    const refreshLogsButton = document.getElementById('refreshLogsButton');
    const logContentArea = document.getElementById('logContent'); // Zone où le texte des logs est affiché
    const logsModalMessageDiv = document.getElementById('logsModalMessage'); // Message pour la modale des logs

    let currentProcedureTypeId = null;


    // --- Fonctions de gestion des messages ---
    function showAdminMessage(message, type = 'error') {
        adminMessageDiv.textContent = message;
        adminMessageDiv.className = 'admin-message ' + type;
        adminMessageDiv.style.display = 'block';
        adminMessageDiv.style.opacity = '0';
        void adminMessageDiv.offsetWidth;
        adminMessageDiv.style.opacity = '1';
    }
    function hideAdminMessage() {
        adminMessageDiv.style.display = 'none';
        adminMessageDiv.textContent = '';
        adminMessageDiv.className = 'admin-message';
    }

    function showFieldManageMessage(message, type = 'error') {
        fieldManageMessageDiv.textContent = message;
        fieldManageMessageDiv.className = 'modal-message ' + type;
        fieldManageMessageDiv.style.display = 'block';
        fieldManageMessageDiv.style.opacity = '0';
        void fieldManageMessageDiv.offsetWidth;
        fieldManageMessageDiv.style.opacity = '1';
    }
    function hideFieldManageMessage() {
        fieldManageMessageDiv.style.display = 'none';
        fieldManageMessageDiv.textContent = '';
        fieldManageMessageDiv.className = 'modal-message';
    }

    function showFieldEditMessage(message, type = 'error') {
        fieldEditMessageDiv.textContent = message;
        fieldEditMessageDiv.className = 'modal-message ' + type;
        fieldEditMessageDiv.style.display = 'block';
        fieldEditMessageDiv.style.opacity = '0';
        void fieldEditMessageDiv.offsetWidth;
        fieldEditMessageDiv.style.opacity = '1';
    }
    function hideFieldEditMessage() {
        fieldEditMessageDiv.style.display = 'none';
        fieldEditMessageDiv.textContent = '';
        fieldEditMessageDiv.className = 'modal-message';
    }

    // Nouveaux messages pour la modale des logs
    function showLogsModalMessage(message, type = 'error') {
        logsModalMessageDiv.textContent = message;
        logsModalMessageDiv.className = 'modal-message ' + type;
        logsModalMessageDiv.style.display = 'block';
        logsModalMessageDiv.style.opacity = '0';
        void logsModalMessageDiv.offsetWidth;
        logsModalMessageDiv.style.opacity = '1';
    }
    function hideLogsModalMessage() {
        logsModalMessageDiv.style.display = 'none';
        logsModalMessageDiv.textContent = '';
        logsModalMessageDiv.className = 'modal-message';
    }


    // --- Fonction d'échappement HTML ---
    function htmlEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // --- Fonctions de chargement des types de procédure ---
    async function loadProcedureTypes() {
        procedureTypesListDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Chargement des types de procédures...</p>';
        try {
            const response = await fetch('/api/v1/procedure-config-api/index.php?action=list_types');
            const data = await response.json();

            if (response.ok && data.success && data.procedure_types) {
                procedureTypesListDiv.innerHTML = '';
                if (data.procedure_types.length === 0) {
                    procedureTypesListDiv.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Aucun type de procédure configuré.</p>';
                } else {
                    data.procedure_types.forEach(type => {
                        const item = document.createElement('div');
                        item.className = `procedure-type-item ${type.is_active ? '' : 'inactive'}`;
                        item.innerHTML = `
                            <div class="procedure-type-info">
                                <strong>${htmlEscape(type.name)}</strong>
                                <p>${htmlEscape(type.description)}</p>
                            </div>
                            <div class="procedure-type-actions">
                                <button class="action-button toggle-active ${type.is_active ? '' : 'inactive-btn'}" data-type-id="${htmlEscape(type.id)}" data-action="${type.is_active ? 'deactivate_type' : 'activate_type'}" data-type-name="${htmlEscape(type.name)}">
                                    <i class="fas ${type.is_active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i> ${type.is_active ? 'Désactiver' : 'Activer'}
                                </button>
                                <button class="action-button manage-fields" data-type-id="${htmlEscape(type.id)}" data-type-name="${htmlEscape(type.name)}" data-action="manage_fields">
                                    <i class="fas fa-edit"></i> Gérer les champs
                                </button>
                            </div>
                        `;
                        procedureTypesListDiv.appendChild(item);
                    });

                    procedureTypesListDiv.querySelectorAll('.toggle-active').forEach(button => {
                        button.addEventListener('click', handleProcedureTypeAction);
                    });
                    procedureTypesListDiv.querySelectorAll('.manage-fields').forEach(button => {
                        button.addEventListener('click', handleProcedureTypeAction);
                    });
                }
            } else {
                showAdminMessage(data.message || 'Erreur lors du chargement des types de procédures.', 'error');
            }
        } catch (error) {
            console.error('Erreur réseau chargement types procédures:', error);
            showAdminMessage('Erreur réseau lors du chargement des types de procédures. Veuillez vérifier votre connexion.', 'error');
        }
    }

    // --- Gestion des actions sur les types de procédure (Activer/Désactiver, Gérer les champs) ---
    async function handleProcedureTypeAction(event) {
        const actionButton = event.currentTarget;
        const typeId = actionButton.dataset.typeId;
        const action = actionButton.dataset.action;
        const typeName = actionButton.dataset.typeName;

        if (!typeId) {
            console.error("typeId est manquant.");
            return;
        }

        if (action === 'activate_type' || action === 'deactivate_type') {
            const confirmMessage = `Voulez-vous vraiment ${action === 'activate_type' ? 'activer' : 'désactiver'} le type de procédure "${typeName}" ?`;
            
            const isConfirmed = await window.showCustomActionModal('Confirmation', confirmMessage, false, '', 'Confirmer', 'primary-button');
            
            if (isConfirmed === null) {
                return; 
            }

            const originalButtonHtml = actionButton.innerHTML;
            actionButton.disabled = true;
            actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

            try {
                const response = await fetch('/api/v1/procedure-config-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, type_id: typeId })
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showAdminMessage(data.message, 'success');
                    loadProcedureTypes();
                } else {
                    showAdminMessage(data.message || `Erreur lors de l'action "${action}".`, 'error');
                }
            } catch (error) {
                console.error('Erreur réseau action type procédure:', error);
                showAdminMessage('Erreur réseau lors de l\'action. Veuillez vérifier votre connexion.', 'error');
            } finally {
                actionButton.disabled = false;
                actionButton.innerHTML = originalButtonHtml;
            }
        } else if (action === 'manage_fields') {
            currentProcedureTypeId = typeId;
            currentProcedureTypeNameSpan.textContent = typeName;
            loadProcedureFields(typeId);
            manageFieldsModal.classList.add('active');
        }
    }

    // --- Fonctions de chargement et gestion des champs ---
    async function loadProcedureFields(typeId) {
        fieldsListContainer.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Chargement des champs...</p>';
        hideFieldManageMessage();
        try {
            const response = await fetch(`/api/v1/procedure-config-api/index.php?action=list_fields&type_id=${typeId}`);
            const data = await response.json();

            if (response.ok && data.success && data.fields) {
                fieldsListContainer.innerHTML = '';
                if (data.fields.length === 0) {
                    fieldsListContainer.innerHTML = '<p style="text-align: center; color: var(--secondary-color);">Aucun champ configuré pour ce type de procédure.</p>';
                } else {
                    data.fields.forEach(field => {
                        const item = document.createElement('div');
                        item.className = `field-item ${field.is_required ? 'required-field' : ''}`;
                        item.innerHTML = `
                            <div class="field-item-info">
                                <span class="field-label">${htmlEscape(field.field_label)}</span>
                                <span class="field-name-type">${htmlEscape(field.field_name)} (${htmlEscape(field.field_type)}) - Ordre: ${field.order_num}</span>
                                ${field.options_json ? `<span class="field-options">Options: ${htmlEscape(JSON.parse(field.options_json).join(', '))}</span>` : ''}
                            </div>
                            <div class="field-item-actions">
                                <button class="action-button primary-button edit-field-button" data-field-id="${htmlEscape(field.id)}" data-action="edit_field">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-button reject-button delete-field-button" data-field-id="${htmlEscape(field.id)}" data-action="delete_field">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                        fieldsListContainer.appendChild(item);
                    });

                    fieldsListContainer.querySelectorAll('.edit-field-button').forEach(button => {
                        button.addEventListener('click', handleFieldAction);
                    });
                    fieldsListContainer.querySelectorAll('.delete-field-button').forEach(button => {
                        button.addEventListener('click', handleFieldAction);
                    });
                }
            } else {
                showFieldManageMessage(data.message || 'Erreur lors du chargement des champs.', 'error');
            }
        } catch (error) {
            console.error('Erreur réseau chargement champs:', error);
            showFieldManageMessage('Erreur réseau lors du chargement des champs. Veuillez vérifier votre connexion.', 'error');
        }
    }

    // --- Gestion des actions sur les champs (Modifier, Supprimer) ---
    async function handleFieldAction(event) {
        const fieldId = event.currentTarget.dataset.fieldId;
        const action = event.currentTarget.dataset.action;

        if (action === 'delete_field') {
            const isConfirmed = await window.showCustomActionModal('Confirmation de suppression', 'Voulez-vous vraiment supprimer ce champ ?', false, '', 'Oui, Supprimer', 'delete-account-button');
            if (isConfirmed === null) return;

            const originalButtonHtml = event.currentTarget.innerHTML;
            event.currentTarget.disabled = true;
            event.currentTarget.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppr...';

            try {
                const response = await fetch('/api/v1/procedure-config-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, field_id: fieldId })
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showFieldManageMessage(data.message, 'success');
                    loadProcedureFields(currentProcedureTypeId);
                } else {
                    showFieldManageMessage(data.message || `Erreur lors de l'action "${action}".`, 'error');
                }
            } catch (error) {
                console.error('Erreur réseau action champ:', error);
                showFieldManageMessage('Erreur réseau lors de l\'action. Veuillez vérifier votre connexion.', 'error');
            } finally {
                event.currentTarget.disabled = false;
                event.currentTarget.innerHTML = originalButtonHtml;
            }
        } else if (action === 'edit_field') {
            await openFieldEditModal(fieldId);
        }
    }

    // --- Fonction pour ouvrir la modale d'ajout/édition de champ ---
    async function openFieldEditModal(fieldId = null) {
        hideFieldEditMessage();
        fieldEditForm.reset();
        optionsGroup.style.display = 'none';
        editFieldId.value = '';

        if (fieldId) {
            fieldEditModalTitle.textContent = 'Modifier un Champ';
            try {
                const response = await fetch(`/api/v1/procedure-config-api/index.php?action=get_field&field_id=${fieldId}`);
                const data = await response.json();
                if (response.ok && data.success && data.field) {
                    const field = data.field;
                    editFieldId.value = field.id;
                    editFieldName.value = field.field_name;
                    editFieldLabel.value = field.field_label;
                    editFieldType.value = field.field_type;
                    editFieldRequired.checked = (field.is_required === 1);
                    if (field.field_type === 'select' || field.field_type === 'checkbox' || field.field_type === 'radio') {
                        optionsGroup.style.display = 'flex';
                        editFieldOptions.value = field.options_json ? JSON.parse(field.options_json).join(', ') : '';
                    }
                    editFieldOrder.value = field.order_num;
                } else {
                    showFieldEditMessage(data.message || 'Erreur chargement détails du champ.', 'error');
                    return;
                }
            } catch (error) {
                console.error('Erreur réseau chargement détail champ:', error);
                showFieldEditMessage('Erreur réseau lors du chargement des détails du champ. Veuillez vérifier votre connexion.', 'error');
                return;
            }
        } else {
            fieldEditModalTitle.textContent = 'Ajouter un nouveau Champ';
            editFieldOrder.value = 0;
            editFieldType.value = ''; 
        }
        editFieldProcedureTypeId.value = currentProcedureTypeId;

        fieldEditModal.classList.add('active');
    }

    // Afficher/Cacher le groupe d'options en fonction du type de champ
    if (editFieldType) {
        editFieldType.addEventListener('change', () => {
            if (editFieldType.value === 'select' || editFieldType.value === 'checkbox' || editFieldType.value === 'radio') {
                optionsGroup.style.display = 'flex';
            } else {
                optionsGroup.style.display = 'none';
                editFieldOptions.value = '';
            }
        });
    }

    // --- Gestion de la soumission du formulaire d'ajout/édition de champ ---
    if (fieldEditForm) {
        fieldEditForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideFieldEditMessage();

            const action = editFieldId.value ? 'update_field' : 'add_field';
            const formData = {
                action: action,
                procedure_type_id: editFieldProcedureTypeId.value,
                field_id: editFieldId.value,
                field_name: editFieldName.value.trim(),
                field_label: editFieldLabel.value.trim(),
                field_type: editFieldType.value,
                is_required: editFieldRequired.checked,
                order_num: parseInt(editFieldOrder.value, 10) || 0
            };

            if (formData.field_type === 'select' || formData.field_type === 'checkbox' || formData.field_type === 'radio') {
                const optionsArray = editFieldOptions.value.split(',').map(item => item.trim()).filter(item => item !== '');
                formData.options_json = JSON.stringify(optionsArray);
            } else {
                formData.options_json = null;
            }

            if (!formData.field_name || !formData.field_label || !formData.field_type) {
                showFieldEditMessage("Veuillez remplir tous les champs obligatoires (Nom, Libellé, Type).", 'error');
                return;
            }
            if ((formData.field_type === 'select' || formData.field_type === 'checkbox' || formData.field_type === 'radio') && (!formData.options_json || JSON.parse(formData.options_json).length === 0)) {
                showFieldEditMessage("Veuillez fournir au moins une option pour ce type de champ.", 'error');
                return;
            }


            saveFieldButton.disabled = true;
            saveFieldButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

            try {
                const response = await fetch('/api/v1/procedure-config-api/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showFieldEditMessage(data.message, 'success');
                    loadProcedureFields(currentProcedureTypeId);
                    setTimeout(() => {
                        fieldEditModal.classList.remove('active');
                        hideFieldEditMessage();
                    }, 1500);
                } else {
                    showFieldEditMessage(data.message || `Erreur lors de l'enregistrement du champ.`, 'error');
                }
            } catch (error) {
                console.error('Erreur réseau enregistrement champ:', error);
                showFieldEditMessage('Erreur réseau lors de l\'enregistrement du champ. Veuillez vérifier votre connexion.', 'error');
            } finally {
                saveFieldButton.disabled = false;
                saveFieldButton.innerHTML = '<i class="fas fa-save"></i> Enregistrer le champ';
            }
        });
    }


    // --- Gestion de la fermeture des modales d'administration ---
    if (closeManageFieldsModalButton) {
        closeManageFieldsModalButton.addEventListener('click', () => {
            manageFieldsModal.classList.remove('active');
            hideFieldManageMessage();
        });
    }
    if (manageFieldsModal) {
        manageFieldsModal.addEventListener('click', (event) => {
            if (event.target === manageFieldsModal) {
                manageFieldsModal.classList.remove('active');
                hideFieldManageMessage();
            }
        });
    }

    if (closeFieldEditModalButton) {
        closeFieldEditModalButton.addEventListener('click', () => {
            fieldEditModal.classList.remove('active');
            hideFieldEditMessage();
        });
    }
    if (fieldEditModal) {
        fieldEditModal.addEventListener('click', (event) => {
            if (event.target === fieldEditModal) {
                fieldEditModal.classList.remove('active');
                hideFieldEditMessage();
            }
        });
    }

    // --- Gestion de l'ouverture/fermeture et fonctions de la modale des logs ---
    if (openLogsModalButton) {
        openLogsModalButton.addEventListener('click', () => {
            logsModal.classList.add('active'); // Afficher la modale des logs
            hideLogsModalMessage(); // Cacher les messages précédents
            loadLogs(logTypeSelect.value, parseInt(logLinesInput.value, 10)); // Charger les logs initiaux
        });
    }
    if (closeLogsModalButton) {
        closeLogsModalButton.addEventListener('click', () => {
            logsModal.classList.remove('active');
            hideLogsModalMessage();
            logContentArea.innerHTML = '<pre>Chargement des logs...</pre>'; // Réinitialiser le contenu
        });
    }
    if (logsModal) {
        logsModal.addEventListener('click', (event) => {
            if (event.target === logsModal) {
                logsModal.classList.remove('active');
                hideLogsModalMessage();
                logContentArea.innerHTML = '<pre>Chargement des logs...</pre>';
            }
        });
    }
    if (refreshLogsButton) {
        refreshLogsButton.addEventListener('click', () => {
            loadLogs(logTypeSelect.value, parseInt(logLinesInput.value, 10));
        });
    }
    if (logTypeSelect) {
        logTypeSelect.addEventListener('change', () => {
            loadLogs(logTypeSelect.value, parseInt(logLinesInput.value, 10));
        });
    }
    if (logLinesInput) {
        logLinesInput.addEventListener('change', () => {
            loadLogs(logTypeSelect.value, parseInt(logLinesInput.value, 10));
        });
    }

    // Fonction pour charger les logs depuis l'API
    async function loadLogs(logType = 'error', numLines = 50) {
        logContentArea.innerHTML = '<pre>Chargement des logs...</pre>';
        hideLogsModalMessage();
        try {
            const response = await fetch(`/api/v1/admin-logs-api/index.php?action=get_logs&type=${logType}&lines=${numLines}`);
            const data = await response.json();

            if (response.ok && data.success && data.logs) {
                logContentArea.innerHTML = `<pre>${htmlEscape(data.logs)}</pre>`;
            } else {
                showLogsModalMessage(data.message || 'Erreur lors du chargement des logs.', 'error');
                logContentArea.innerHTML = `<pre style="color:var(--error-color);">Impossible de charger les logs : ${htmlEscape(data.message || 'Erreur inconnue')}</pre>`;
            }
        } catch (error) {
            console.error('Erreur réseau chargement logs:', error);
            showLogsModalMessage('Erreur réseau lors du chargement des logs. Veuillez vérifier votre connexion.', 'error');
            logContentArea.innerHTML = '<pre style="color:var(--error-color);">Erreur réseau. Impossible de charger les logs.</pre>';
        }
    }


    // --- Exécution initiale au chargement de la page ---
    loadProcedureTypes();

    // Écouteur pour le bouton "Ajouter un champ"
    if (addFieldButton) {
        addFieldButton.addEventListener('click', () => openFieldEditModal());
    }
});