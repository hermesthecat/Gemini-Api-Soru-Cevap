const shopHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    // Helper methods to get modules with fallback
    const showToast = (message, type) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (window.ui && window.ui.showToast) {
            window.ui.showToast(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    };

    const getUIGame = () => {
        const UIGame = ModuleLoader?.getModule('UIGame');
        if (UIGame) {
            return UIGame;
        } else if (window.ui) {
            return window.ui;
        }
        return null;
    };

    const loadShop = async () => {
        const result = await api.call('get_shop_items', {}, 'POST', false);
        if (result.success) {
            const uiGame = getUIGame();
            if (uiGame && uiGame.renderShop) {
                uiGame.renderShop(result.data);
            }
            // Also update the coin balance display, as the user might navigate here
            // after gaining coins, and the header might not be up-to-date.
            const userDataResult = await api.call('get_user_data', {}, 'POST', false);
            if (userDataResult.success) {
                const uiGame = getUIGame();
                if (uiGame && uiGame.updateCoinBalance) {
                    uiGame.updateCoinBalance(userDataResult.data.coins);
                }
            }
        }
    };

    const handlePurchase = async (itemKey, price) => {
        const currentUserCoinBalance = parseInt(dom.userCoinBalance.textContent, 10);
        if (currentUserCoinBalance < price) {
            showToast('Yetersiz jeton!', 'error');
            return;
        }

        if (confirm(`Bu ürünü ${price} jeton karşılığında satın almak istediğinizden emin misiniz?`)) {
            const result = await api.call('purchase_lifeline', { item_key: itemKey });
            if (result.success) {
                showToast(result.message, 'success');
                const uiGame = getUIGame();
                if (uiGame && uiGame.updateCoinBalance) {
                    uiGame.updateCoinBalance(result.data.new_coin_balance);
                }
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