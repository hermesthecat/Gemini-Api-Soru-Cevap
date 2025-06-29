const shopHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const loadShop = async () => {
        const result = await api.call('get_shop_items', {}, 'POST', false);
        if (result.success) {
            ui.renderShop(result.data);
            // Also update the coin balance display, as the user might navigate here
            // after gaining coins, and the header might not be up-to-date.
            const userDataResult = await api.call('get_user_data', {}, 'POST', false);
            if (userDataResult.success) {
                ui.updateCoinBalance(userDataResult.data.coins);
            }
        }
    };

    const handlePurchase = async (itemKey, price) => {
        const currentUserCoinBalance = parseInt(dom.userCoinBalance.textContent, 10);
        if (currentUserCoinBalance < price) {
            ui.showToast('Yetersiz jeton!', 'error');
            return;
        }

        if (confirm(`Bu ürünü ${price} jeton karşılığında satın almak istediğinizden emin misiniz?`)) {
            const result = await api.call('purchase_lifeline', { item_key: itemKey });
            if (result.success) {
                ui.showToast(result.message, 'success');
                ui.updateCoinBalance(result.data.new_coin_balance);
                // Refresh shop to show new stock
                loadShop();
                // Refresh game lifelines in case game is in background
                 const sessionResult = await api.call('check_session', {}, 'POST', false);
                 if (sessionResult.success) {
                    appState.set('lifelines', sessionResult.data.lifelines);
                    game.updateLifelineUI();
                 }
            }
        }
    };

    const addEventListeners = () => {
        dom.shopItemsContainer?.addEventListener('click', (e) => {
            const purchaseBtn = e.target.closest('.purchase-lifeline-btn');
            if (purchaseBtn && !purchaseBtn.disabled) {
                const itemKey = purchaseBtn.dataset.itemKey;
                const price = purchaseBtn.dataset.price;
                handlePurchase(itemKey, parseInt(price, 10));
            }
        });
    };

    return {
        init,
        loadShop
    };
})(); 