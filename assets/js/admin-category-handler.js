const categoryHandler = (() => {
    let categories = [];

    const loadCategories = async () => {
        try {
            const response = await api.call('admin_get_categories', {}, 'POST', false);
            if (response.success) {
                categories = response.data;
                renderCategories();
            } else {
                ui.showToast('Kategoriler yüklenemedi: ' + response.message, 'error');
            }
        } catch (error) {
            ui.showToast('Ağ hatası: ' + error.message, 'error');
        }
    };

    const renderCategories = () => {
        const tbody = document.getElementById('categories-list-body');
        if (!tbody) return;

        if (categories.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        <div class="py-8">
                            <i class="fas fa-folder-open text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                            <p>Henüz kategori bulunmuyor.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = categories.map(category => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="w-10 h-10 bg-${category.color}-100 dark:bg-${category.color}-900 rounded-lg flex items-center justify-center">
                                <i class="fas ${category.icon} text-${category.color}-600 dark:text-${category.color}-400"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">${category.category_name}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas ${category.icon} mr-1"></i>${category.color}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded text-sm">${category.category_key}</code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${category.question_count}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${category.is_active === '1' ?
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>' :
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Pasif</span>'
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="categoryHandler.editCategory(${category.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                        <i class="fas fa-edit mr-1"></i>Düzenle
                    </button>
                    ${category.question_count === '0' ? `
                        <button onclick="categoryHandler.deleteCategory(${category.id}, '${category.category_name}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            <i class="fas fa-trash mr-1"></i>Sil
                        </button>
                    ` : `
                        <span class="text-gray-400 text-xs">Silinemez</span>
                    `}
                </td>
            </tr>
        `).join('');
    };

    const addCategory = async (formData) => {
        try {
            const response = await api.call('admin_add_category', formData, 'POST', true);
            if (response.success) {
                ui.showToast(response.message, 'success');
                loadCategories();
                return true;
            } else {
                ui.showToast(response.message, 'error');
                return false;
            }
        } catch (error) {
            ui.showToast('Ağ hatası: ' + error.message, 'error');
            return false;
        }
    };

    const editCategory = (id) => {
        const category = categories.find(cat => cat.id == id);
        if (!category) return;

        document.getElementById('edit-category-id').value = category.id;
        document.getElementById('edit-category-name').value = category.category_name;
        document.getElementById('edit-icon').value = category.icon;
        document.getElementById('edit-color').value = category.color;
        document.getElementById('edit-is-active').checked = category.is_active === '1';

        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-modal').classList.add('flex');
    };

    const updateCategory = async (formData) => {
        try {
            const response = await api.call('admin_update_category', formData, 'POST', true);
            if (response.success) {
                ui.showToast(response.message, 'success');
                closeEditModal();
                loadCategories();
                return true;
            } else {
                ui.showToast(response.message, 'error');
                return false;
            }
        } catch (error) {
            ui.showToast('Ağ hatası: ' + error.message, 'error');
            return false;
        }
    };

    const deleteCategory = async (id, name) => {
        if (!confirm(`"${name}" kategorisini silmek istediğinizden emin misiniz?`)) {
            return;
        }

        try {
            const response = await api.call('admin_delete_category', { id: id }, 'POST', true);
            if (response.success) {
                ui.showToast(response.message, 'success');
                loadCategories();
            } else {
                ui.showToast(response.message, 'error');
            }
        } catch (error) {
            ui.showToast('Ağ hatası: ' + error.message, 'error');
        }
    };

    const closeEditModal = () => {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-modal').classList.remove('flex');
    };

    const init = () => {
        // Setup event listeners
        const addForm = document.getElementById('add-category-form');
        if (addForm) {
            addForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());

                const success = await addCategory(data);
                if (success) {
                    e.target.reset();
                    document.getElementById('color').value = 'gray';
                    document.getElementById('icon').value = 'fa-question';
                }
            });
        }

        const editForm = document.getElementById('edit-category-form');
        if (editForm) {
            document.getElementById('save-edit').addEventListener('click', async () => {
                const formData = new FormData(editForm);
                const data = Object.fromEntries(formData.entries());
                data.is_active = document.getElementById('edit-is-active').checked ? 1 : 0;
                await updateCategory(data);
            });
        }

        const cancelEdit = document.getElementById('cancel-edit');
        if (cancelEdit) {
            cancelEdit.addEventListener('click', closeEditModal);
        }

        // Load categories on init
        loadCategories();
    };

    return {
        init,
        loadCategories,
        addCategory,
        editCategory,
        updateCategory,
        deleteCategory,
        closeEditModal
    };
})();