const adminShopHandler = (() => {
    let dom = {};

    const init = (domElements = {}) => {
        dom = domElements;
        addEventListeners();
    };

    const addEventListeners = () => {
        const shopSettingsForm = document.getElementById('shop-settings-form');
        if (shopSettingsForm) {
            shopSettingsForm.addEventListener('submit', handleShopSettingsSubmit);
        }
    };

    const loadShopData = async () => {
        try {
            const result = await api.call('admin_get_shop_stats', {}, 'POST', false);

            if (result && result.success) {
                renderShopStats(result.data);
                populateShopForm(result.data.current_prices);
            } else {
                const message = result?.message || 'Mağaza verileri yüklenirken hata oluştu';
            }
        } catch (error) {
        }
    };

    const renderShopStats = (data) => {
        // Üst istatistik kartları
        const totalSalesEl = document.getElementById('total-sales');
        const totalRevenueEl = document.getElementById('total-revenue');
        const mostPopularEl = document.getElementById('most-popular');

        if (totalSalesEl) totalSalesEl.textContent = data.total_sales || '0';
        if (totalRevenueEl) totalRevenueEl.textContent = (data.total_revenue || 0) + ' Jeton';
        if (mostPopularEl) mostPopularEl.textContent = data.most_popular || 'Henüz satış yok';

        // Detaylı istatistikler tablosu
        renderDetailedStats(data.detailed_stats || []);

        // Son satışlar tablosu
        renderRecentSales(data.recent_sales || []);
    };

    const renderDetailedStats = (stats) => {
        const tbody = document.getElementById('shop-stats-body');
        if (!tbody) return;

        if (stats.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Henüz satış verisi bulunmamaktadır.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = stats.map(stat => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="p-2 ${getJokerColor(stat.item_type)} rounded-lg mr-3">
                            <i class="fas ${getJokerIcon(stat.item_type)} text-white"></i>
                        </div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${stat.item_display_name}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${stat.sales_count}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${Math.round(stat.total_revenue)} Jeton
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${parseFloat(stat.daily_avg).toFixed(1)}/gün
                </td>
            </tr>
        `).join('');
    };

    const renderRecentSales = (sales) => {
        const tbody = document.getElementById('recent-sales-body');
        if (!tbody) return;

        if (sales.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Henüz satış yapılmamıştır.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = sales.map(sale => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xs mr-3">
                            ${sale.username.charAt(0).toUpperCase()}
                        </div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${sale.username}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="p-1 ${getJokerColor(sale.item_type)} rounded mr-2">
                            <i class="fas ${getJokerIcon(sale.item_type)} text-white text-xs"></i>
                        </div>
                        <span class="text-sm text-gray-900 dark:text-white">${sale.item_display_name}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    ${sale.item_price} Jeton
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    ${new Date(sale.purchase_date).toLocaleDateString('tr-TR', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}
                </td>
            </tr>
        `).join('');
    };

    const populateShopForm = (currentPrices) => {
        // Mevcut fiyatları forma doldur
        currentPrices.forEach(item => {
            let inputId = '';
            if (item.name === '50/50 Jokeri') {
                inputId = 'fifty-fifty-price';
            } else if (item.name === '+15 Saniye Jokeri') {
                inputId = 'extra-time-price';
            } else if (item.name === 'Soruyu Geç Jokeri') {
                inputId = 'pass-price';
            }

            if (inputId) {
                const input = document.getElementById(inputId);
                if (input) {
                    input.value = item.price;
                }
            }
        });
    };

    const handleShopSettingsSubmit = async (event) => {
        event.preventDefault();

        const formData = new FormData(event.target);
        const prices = {};

        // Form verilerini topla
        for (let [key, value] of formData.entries()) {
            const numericValue = parseInt(value, 10);
            if (numericValue > 0 && numericValue <= 1000) {
                prices[key] = numericValue;
            }
        }

        if (Object.keys(prices).length === 0) {
            ui.showToast('Lütfen geçerli fiyatlar girin (1-1000 arası)', 'error');
            return;
        }

        try {
            const result = await api.call('admin_update_shop_prices', { prices });

            if (result.success) {
                // Başarılı güncelleme sonrası verileri yenile
                setTimeout(() => {
                    loadShopData();
                }, 1000);
            }
        } catch (error) {
        }
    };

    const getJokerColor = (itemType) => {
        const colors = {
            'fiftyFifty': 'bg-blue-500',
            'extraTime': 'bg-green-500',
            'pass': 'bg-purple-500'
        };
        return colors[itemType] || 'bg-gray-500';
    };

    const getJokerIcon = (itemType) => {
        const icons = {
            'fiftyFifty': 'fa-balance-scale',
            'extraTime': 'fa-clock',
            'pass': 'fa-forward'
        };
        return icons[itemType] || 'fa-star';
    };

    return {
        init,
        loadShopData
    };
})();