const questHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
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

    const updateQuests = async () => {
        const result = await api.call('get_daily_quests', {}, 'POST', false);
        console.log('Quest API result:', result);

        if (result.success) {
            const uiGame = getUIGame();
            console.log('UIGame module:', uiGame);
            if (uiGame && uiGame.renderQuests) {
                console.log('Rendering quests:', result.data);
                uiGame.renderQuests(result.data);
            } else {
                console.error('UIGame.renderQuests not found!');
            }
        } else {
            console.error('Quest API failed:', result);
            const uiGame = getUIGame();
            if (uiGame && uiGame.renderQuests) {
                uiGame.renderQuests([]); // Hata durumunda boş liste render et
            }
        }
    };

    const handleQuestCompletion = (completedQuests) => {
        if (!completedQuests || completedQuests.length === 0) return;

        setTimeout(() => {
            for (const quest of completedQuests) {
                const message = `Görev Tamamlandı: "${quest.name}" (+${quest.reward_points} Puan & +${quest.reward_coins} Jeton!)`;
                showToast(message, 'success');
                // Puan animasyonu vs eklenebilir
                document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'achievement' } }));
            }
            // Görev listesini ve kullanıcı verilerini güncelle
            updateQuests();
            statsHandler.updateUserData();
        }, 1000); // Diğer animasyonların bitmesi için küçük bir gecikme
    };

    return {
        init,
        updateQuests,
        handleQuestCompletion
    };
})(); 