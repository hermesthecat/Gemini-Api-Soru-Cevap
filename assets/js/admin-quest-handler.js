const adminQuestHandler = (() => {
    let dom = {};

    const init = () => {
        dom = {
            createQuestForm: document.getElementById('create-quest-form'),
            editQuestForm: document.getElementById('edit-quest-form'),
            editQuestModal: document.getElementById('edit-quest-modal'),
            editModalCloseBtn: document.getElementById('edit-modal-close-btn'),
            editModalCancelBtn: document.getElementById('edit-modal-cancel-btn'),
            refreshQuestsBtn: document.getElementById('refresh-quests-btn'),
            questsListBody: document.getElementById('quests-list-body'),

            // Form elements
            questType: document.getElementById('quest-type'),
            questTarget: document.getElementById('quest-target'),

            // Stats elements
            totalQuests: document.getElementById('total-quests'),
            activeQuests: document.getElementById('active-quests'),
            assignedToday: document.getElementById('assigned-today'),
            completedToday: document.getElementById('completed-today')
        };

        setupEventListeners();
        updateTargetHelperText();
    };

    const setupEventListeners = () => {
        // Create quest form
        dom.createQuestForm?.addEventListener('submit', handleCreateQuest);

        // Edit quest form
        dom.editQuestForm?.addEventListener('submit', handleEditQuest);

        // Modal controls
        dom.editModalCloseBtn?.addEventListener('click', closeEditModal);
        dom.editModalCancelBtn?.addEventListener('click', closeEditModal);

        // Refresh button
        dom.refreshQuestsBtn?.addEventListener('click', loadQuestsList);

        // Quest type change handler
        dom.questType?.addEventListener('change', updateTargetHelperText);

        // Modal backdrop click
        dom.editQuestModal?.addEventListener('click', (e) => {
            if (e.target === dom.editQuestModal) {
                closeEditModal();
            }
        });
    };

    const updateTargetHelperText = () => {
        const questType = dom.questType?.value;
        const targetField = dom.questTarget;
        const targetLabel = document.querySelector('label[for="quest-target"]');

        if (!targetField || !targetLabel) return;

        switch (questType) {
            case 'solve_category':
                targetField.placeholder = 'tarih, cografya, matematik, vs.';
                targetLabel.innerHTML = 'Hedef Kategori <span class="text-red-500">*</span>';
                targetField.required = true;
                break;
            case 'solve_difficulty':
                targetField.placeholder = 'kolay, orta, zor';
                targetLabel.innerHTML = 'Hedef Zorluk <span class="text-red-500">*</span>';
                targetField.required = true;
                break;
            case 'consecutive_days':
                targetField.placeholder = 'Boş bırakın';
                targetLabel.innerHTML = 'Hedef Değer';
                targetField.required = false;
                targetField.value = '';
                break;
            case 'win_duels':
                targetField.placeholder = 'Boş bırakın';
                targetLabel.innerHTML = 'Hedef Değer';
                targetField.required = false;
                targetField.value = '';
                break;
            default:
                targetField.placeholder = 'Opsiyonel hedef değer';
                targetLabel.innerHTML = 'Hedef Değer';
                targetField.required = false;
        }
    };

    const handleCreateQuest = async (e) => {
        e.preventDefault();

        const formData = new FormData(dom.createQuestForm);
        const data = Object.fromEntries(formData.entries());

        // Checkbox değerini boolean'a çevir
        data.is_active = formData.has('is_active');

        try {
            const result = await api.call('admin_create_quest', data, 'POST', true);

            if (result.success) {
                ui.showToast('Quest başarıyla oluşturuldu!', 'success');
                dom.createQuestForm.reset();
                updateTargetHelperText();
                loadQuestsList();
                loadStats();
            } else {
                ui.showToast(result.message || 'Quest oluşturulurken hata oluştu!', 'error');
            }
        } catch (error) {
            ui.showToast('Bağlantı hatası!', 'error');
            console.error('Create quest error:', error);
        }
    };

    const handleEditQuest = async (e) => {
        e.preventDefault();

        const formData = new FormData(dom.editQuestForm);
        const data = Object.fromEntries(formData.entries());

        // Checkbox değerini boolean'a çevir
        data.is_active = formData.has('is_active');

        try {
            const result = await api.call('admin_update_quest', data, 'POST', true);

            if (result.success) {
                ui.showToast('Quest başarıyla güncellendi!', 'success');
                closeEditModal();
                loadQuestsList();
                loadStats();
            } else {
                ui.showToast(result.message || 'Quest güncellenirken hata oluştu!', 'error');
            }
        } catch (error) {
            ui.showToast('Bağlantı hatası!', 'error');
            console.error('Update quest error:', error);
        }
    };

    const loadQuestsList = async () => {
        try {
            const result = await api.call('admin_get_quests', {}, 'POST', false);

            if (result.success) {
                renderQuestsList(result.data);
            } else {
                ui.showToast('Quest listesi yüklenemedi!', 'error');
            }
        } catch (error) {
            ui.showToast('Bağlantı hatası!', 'error');
            console.error('Load quests error:', error);
        }
    };

    const renderQuestsList = (quests) => {
        if (!dom.questsListBody) return;

        dom.questsListBody.innerHTML = '';

        if (!quests || quests.length === 0) {
            dom.questsListBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Henüz hiç quest bulunmuyor.
                    </td>
                </tr>
            `;
            return;
        }

        quests.forEach(quest => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';

            const questTypeInfo = getQuestTypeInfo(quest.type);
            const statusColor = quest.is_active == 1 ? 'text-green-600' : 'text-red-600';
            const statusText = quest.is_active == 1 ? 'Aktif' : 'Pasif';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">${quest.name}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">${quest.quest_key}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${questTypeInfo.bgColor} ${questTypeInfo.textColor}">
                        ${questTypeInfo.typeName}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">
                        ${quest.target || '-'} / ${quest.default_goal}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">
                        <span class="text-blue-600">${quest.reward_points}P</span> &
                        <span class="text-yellow-600">${quest.reward_coins}J</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium ${statusColor}">${statusText}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="adminQuestHandler.editQuest('${quest.quest_key}')"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                        Düzenle
                    </button>
                    <button onclick="adminQuestHandler.deleteQuest('${quest.quest_key}', '${quest.name}')"
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                        Sil
                    </button>
                </td>
            `;

            dom.questsListBody.appendChild(row);
        });
    };

    const getQuestTypeInfo = (type) => {
        switch (type) {
            case 'solve_category':
                return {
                    bgColor: 'bg-blue-100',
                    textColor: 'text-blue-800',
                    typeName: 'Kategori'
                };
            case 'solve_difficulty':
                return {
                    bgColor: 'bg-purple-100',
                    textColor: 'text-purple-800',
                    typeName: 'Zorluk'
                };
            case 'consecutive_days':
                return {
                    bgColor: 'bg-orange-100',
                    textColor: 'text-orange-800',
                    typeName: 'Giriş Serisi'
                };
            case 'win_duels':
                return {
                    bgColor: 'bg-red-100',
                    textColor: 'text-red-800',
                    typeName: 'Düello'
                };
            default:
                return {
                    bgColor: 'bg-gray-100',
                    textColor: 'text-gray-800',
                    typeName: 'Genel'
                };
        }
    };

    const editQuest = async (questKey) => {
        try {
            const result = await api.call('admin_get_quests', {}, 'POST', false);

            if (result.success) {
                const quest = result.data.find(q => q.quest_key === questKey);
                if (quest) {
                    // Form alanlarını doldur
                    document.getElementById('edit-quest-id').value = quest.quest_key;
                    document.getElementById('edit-quest-name').value = quest.name;
                    document.getElementById('edit-quest-description').value = quest.description_template;
                    document.getElementById('edit-quest-target').value = quest.target || '';
                    document.getElementById('edit-quest-goal').value = quest.default_goal;
                    document.getElementById('edit-quest-reward-points').value = quest.reward_points;
                    document.getElementById('edit-quest-reward-coins').value = quest.reward_coins;
                    document.getElementById('edit-quest-active').checked = quest.is_active == 1;

                    dom.editQuestModal.classList.remove('hidden');
                }
            }
        } catch (error) {
            ui.showToast('Quest bilgileri yüklenemedi!', 'error');
            console.error('Load quest error:', error);
        }
    };

    const deleteQuest = async (questKey, questName) => {
        if (!confirm(`"${questName}" quest'ini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve tüm ilgili veriler silinecektir.`)) {
            return;
        }

        try {
            const result = await api.call('admin_delete_quest', { quest_key: questKey }, 'POST', true);

            if (result.success) {
                ui.showToast('Quest başarıyla silindi!', 'success');
                loadQuestsList();
                loadStats();
            } else {
                ui.showToast(result.message || 'Quest silinirken hata oluştu!', 'error');
            }
        } catch (error) {
            ui.showToast('Bağlantı hatası!', 'error');
            console.error('Delete quest error:', error);
        }
    };

    const closeEditModal = () => {
        dom.editQuestModal?.classList.add('hidden');
        dom.editQuestForm?.reset();
    };

    const loadStats = async () => {
        try {
            const result = await api.call('admin_get_quest_stats', {}, 'POST', false);

            if (result.success) {
                const stats = result.data;

                dom.totalQuests.textContent = stats.total_quests || '0';
                dom.activeQuests.textContent = stats.active_quests || '0';
                dom.assignedToday.textContent = stats.assigned_today || '0';
                dom.completedToday.textContent = stats.completed_today || '0';
            }
        } catch (error) {
            console.error('Load stats error:', error);
        }
    };

    return {
        init,
        loadQuestsList,
        loadStats,
        editQuest,
        deleteQuest
    };
})();