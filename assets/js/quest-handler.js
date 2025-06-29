const questHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
    };

    const updateQuests = async () => {
        const result = await api.call('get_daily_quests', {}, 'POST', false);
        if (result.success) {
            ui.renderQuests(result.data);
        } else {
            ui.renderQuests([]); // Hata durumunda boş liste render et
        }
    };

    const handleQuestCompletion = (completedQuests) => {
        if (!completedQuests || completedQuests.length === 0) return;

        setTimeout(() => {
            for (const quest of completedQuests) {
                const message = `Görev Tamamlandı: "${quest.name}" (+${quest.reward_points} Puan & +${quest.reward_coins} Jeton!)`;
                ui.showToast(message, 'success');
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