const adminAchievementHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        if (domElements) {
            dom = domElements;
        } else {
            // Default DOM elements
            dom = {
                createForm: document.getElementById('create-achievement-form'),
                editForm: document.getElementById('edit-achievement-form'),
                editModal: document.getElementById('edit-achievement-modal'),
                editModalCloseBtn: document.getElementById('edit-modal-close-btn'),
                editModalCancelBtn: document.getElementById('edit-modal-cancel-btn'),
                achievementsListBody: document.getElementById('achievements-list-body'),
                refreshBtn: document.getElementById('refresh-achievements-btn'),
                totalAchievements: document.getElementById('total-achievements'),
                trackingEnabled: document.getElementById('tracking-enabled'),
                totalEarned: document.getElementById('total-earned'),
                averageCompletion: document.getElementById('average-completion')
            };
        }
        addEventListeners();
    };

    const addEventListeners = () => {
        if (dom.createForm) {
            dom.createForm.addEventListener('submit', handleCreateAchievement);
        }

        if (dom.editForm) {
            dom.editForm.addEventListener('submit', handleEditAchievement);
        }

        if (dom.editModalCloseBtn) {
            dom.editModalCloseBtn.addEventListener('click', closeEditModal);
        }

        if (dom.editModalCancelBtn) {
            dom.editModalCancelBtn.addEventListener('click', closeEditModal);
        }

        if (dom.refreshBtn) {
            dom.refreshBtn.addEventListener('click', () => {
                loadAchievementsList();
                loadStats();
            });
        }

        // Rule type değişikliğinde hedef değer placeholder'ını güncelle
        const ruleTypeSelect = document.getElementById('rule-type');
        if (ruleTypeSelect) {
            ruleTypeSelect.addEventListener('change', updateTargetValuePlaceholder);
        }
    };

    const updateTargetValuePlaceholder = () => {
        const ruleType = document.getElementById('rule-type').value;
        const targetValueInput = document.getElementById('target-value');

        if (!targetValueInput) return;

        const placeholders = {
            'first_correct': 'Boş bırakın',
            'total_score': 'Boş bırakın',
            'night_hours': '00:00-04:00',
            'all_categories': 'Boş bırakın',
            'collect_achievements': 'Boş bırakın',
            'category_expert': 'tarih, spor, bilim vs.',
            'category_perfect': 'tarih, spor, bilim vs.',
            'difficulty_expert': 'kolay, orta, zor',
            'consecutive_correct': 'Boş bırakın',
            'speed_answer': 'Saniye (5)'
        };

        targetValueInput.placeholder = placeholders[ruleType] || 'Opsiyonel';
    };

    const handleCreateAchievement = async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            achievement_key: formData.get('achievement_key'),
            name: formData.get('name'),
            description: formData.get('description'),
            icon: formData.get('icon'),
            color: formData.get('color'),
            rule_type: formData.get('rule_type'),
            target_value: formData.get('target_value') || null,
            goal_count: parseInt(formData.get('goal_count')),
            tracking_enabled: formData.get('tracking_enabled') === 'on'
        };

        // Validasyon
        if (!data.achievement_key.match(/^[a-z0-9_]+$/)) {
            ui.showToast('Başarım anahtarı sadece küçük harf, sayı ve alt çizgi içerebilir!', 'error');
            return;
        }

        try {
            ui.showLoading(true);
            const response = await api.call('admin_create_achievement', data, 'POST');

            if (response.success) {
                ui.showToast('Başarım başarıyla oluşturuldu!', 'success');
                e.target.reset();
                loadAchievementsList();
                loadStats();
            } else {
                ui.showToast(response.message || 'Başarım oluşturulurken hata oluştu!', 'error');
            }
        } catch (error) {
            console.error('Create achievement error:', error);
            ui.showToast('Bir hata oluştu!', 'error');
        } finally {
            ui.showLoading(false);
        }
    };

    const handleEditAchievement = async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            achievement_key: formData.get('achievement_key'),
            name: formData.get('name'),
            description: formData.get('description'),
            icon: formData.get('icon'),
            color: formData.get('color'),
            goal_count: parseInt(formData.get('goal_count')),
            tracking_enabled: formData.get('tracking_enabled') === 'on'
        };

        try {
            ui.showLoading(true);
            const response = await api.call('admin_update_achievement', data, 'POST');

            if (response.success) {
                ui.showToast('Başarım başarıyla güncellendi!', 'success');
                closeEditModal();
                loadAchievementsList();
                loadStats();
            } else {
                ui.showToast(response.message || 'Başarım güncellenirken hata oluştu!', 'error');
            }
        } catch (error) {
            console.error('Update achievement error:', error);
            ui.showToast('Bir hata oluştu!', 'error');
        } finally {
            ui.showLoading(false);
        }
    };

    const loadAchievementsList = async () => {
        try {
            const response = await api.call('admin_get_achievements', {}, 'POST');

            if (response.success) {
                renderAchievementsList(response.data);
            } else {
                console.error('Failed to load achievements:', response.message);
                ui.showToast('Başarımlar yüklenirken hata oluştu!', 'error');
            }
        } catch (error) {
            console.error('Load achievements error:', error);
            ui.showToast('Başarımlar yüklenirken hata oluştu!', 'error');
        }
    };

    const loadStats = async () => {
        try {
            const response = await api.call('admin_get_achievement_stats', {}, 'POST');

            if (response.success) {
                const stats = response.data;

                if (dom.totalAchievements) {
                    dom.totalAchievements.textContent = stats.total_achievements || '0';
                }
                if (dom.trackingEnabled) {
                    dom.trackingEnabled.textContent = stats.tracking_enabled || '0';
                }
                if (dom.totalEarned) {
                    dom.totalEarned.textContent = stats.total_earned || '0';
                }
                if (dom.averageCompletion) {
                    dom.averageCompletion.textContent = `${stats.average_completion || 0}%`;
                }
            }
        } catch (error) {
            console.error('Load stats error:', error);
        }
    };

    const renderAchievementsList = (achievements) => {
        if (!dom.achievementsListBody) return;

        dom.achievementsListBody.innerHTML = '';

        if (!achievements || achievements.length === 0) {
            dom.achievementsListBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Henüz başarım bulunmuyor.
                    </td>
                </tr>
            `;
            return;
        }

        achievements.forEach(achievement => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700';

            const ruleTypeLabels = {
                'first_correct': 'İlk Doğru',
                'total_score': 'Toplam Puan',
                'night_hours': 'Gece Saatleri',
                'all_categories': 'Tüm Kategoriler',
                'collect_achievements': 'Başarım Topla',
                'category_expert': 'Kategori Uzmanı',
                'category_perfect': 'Kategori Kusursuz',
                'difficulty_expert': 'Zorluk Uzmanı',
                'consecutive_correct': 'Ardışık Doğru',
                'speed_answer': 'Hızlı Cevap'
            };

            const ruleLabel = ruleTypeLabels[achievement.rule_type] || achievement.rule_type;
            const targetText = achievement.target_value
                ? `${ruleLabel} (${achievement.target_value})`
                : ruleLabel;

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-${achievement.color}-500 flex items-center justify-center mr-3">
                            <i class="fas ${achievement.icon} text-white"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">${achievement.name}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">${achievement.achievement_key}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">${targetText}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${achievement.description}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${achievement.goal_count}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        achievement.tracking_enabled
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                    }">
                        ${achievement.tracking_enabled ? 'Aktif' : 'Pasif'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${achievement.earned_count || 0}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="adminAchievementHandler.editAchievement('${achievement.achievement_key}')"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 mr-3">
                        <i class="fas fa-edit"></i> Düzenle
                    </button>
                    <button onclick="adminAchievementHandler.toggleTracking('${achievement.achievement_key}', ${!achievement.tracking_enabled})"
                            class="text-${achievement.tracking_enabled ? 'red' : 'green'}-600 hover:text-${achievement.tracking_enabled ? 'red' : 'green'}-900 dark:text-${achievement.tracking_enabled ? 'red' : 'green'}-400 mr-3">
                        <i class="fas fa-${achievement.tracking_enabled ? 'pause' : 'play'}"></i> ${achievement.tracking_enabled ? 'Durdur' : 'Başlat'}
                    </button>
                    <button onclick="adminAchievementHandler.deleteAchievement('${achievement.achievement_key}')"
                            class="text-red-600 hover:text-red-900 dark:text-red-400">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </td>
            `;

            dom.achievementsListBody.appendChild(row);
        });
    };

    const editAchievement = async (achievementKey) => {
        try {
            const response = await api.call('admin_get_achievement_details', { achievement_key: achievementKey }, 'POST');

            if (response.success) {
                const achievement = response.data;

                // Modal'ı doldur
                document.getElementById('edit-achievement-id').value = achievement.achievement_key;
                document.getElementById('edit-achievement-name').value = achievement.name;
                document.getElementById('edit-achievement-description').value = achievement.description;
                document.getElementById('edit-achievement-icon').value = achievement.icon;
                document.getElementById('edit-achievement-color').value = achievement.color;
                document.getElementById('edit-goal-count').value = achievement.goal_count;
                document.getElementById('edit-tracking-enabled').checked = achievement.tracking_enabled;

                // Modal'ı göster
                if (dom.editModal) {
                    dom.editModal.classList.remove('hidden');
                }
            } else {
                ui.showToast(response.message || 'Başarım detayları alınamadı!', 'error');
            }
        } catch (error) {
            console.error('Get achievement details error:', error);
            ui.showToast('Bir hata oluştu!', 'error');
        }
    };

    const toggleTracking = async (achievementKey, enable) => {
        try {
            const response = await api.call('admin_toggle_achievement_tracking', {
                achievement_key: achievementKey,
                tracking_enabled: enable
            }, 'POST');

            if (response.success) {
                ui.showToast(`Tracking ${enable ? 'etkinleştirildi' : 'durduruldu'}!`, 'success');
                loadAchievementsList();
                loadStats();
            } else {
                ui.showToast(response.message || 'Tracking durumu değiştirilemedi!', 'error');
            }
        } catch (error) {
            console.error('Toggle tracking error:', error);
            ui.showToast('Bir hata oluştu!', 'error');
        }
    };

    const deleteAchievement = async (achievementKey) => {
        if (!confirm('Bu başarımı silmek istediğinizden emin misiniz?')) {
            return;
        }

        try {
            const response = await api.call('admin_delete_achievement', { achievement_key: achievementKey }, 'POST');

            if (response.success) {
                ui.showToast('Başarım başarıyla silindi!', 'success');
                loadAchievementsList();
                loadStats();
            } else {
                ui.showToast(response.message || 'Başarım silinemedi!', 'error');
            }
        } catch (error) {
            console.error('Delete achievement error:', error);
            ui.showToast('Bir hata oluştu!', 'error');
        }
    };

    const closeEditModal = () => {
        if (dom.editModal) {
            dom.editModal.classList.add('hidden');
        }
    };

    // Public methods
    return {
        init,
        loadAchievementsList,
        loadStats,
        editAchievement,
        toggleTracking,
        deleteAchievement
    };
})();