const duelHandler = (() => {
    let dom = {};
    let duelState = {}; // Mevcut düello ile ilgili tüm verileri tutacak

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const startDuel = async (duelId) => {
        const result = await api.call('duel_start_game', { duel_id: duelId });
        if (result.success) {
            setupDuel(result.data);
            ui.showView('duel-game-view');
        } else {
            ui.showToast(result.message, 'error');
        }
    };

    const setupDuel = (duelData) => {
        const currentUser = appState.get('currentUser');
        const opponent = duelData.challenger_id === currentUser.id
            ? { id: duelData.opponent_id, username: duelData.opponent_name }
            : { id: duelData.challenger_id, username: duelData.challenger_name };

        duelState = {
            id: duelData.id,
            questions: duelData.questions,
            opponent: opponent,
            myScore: 0,
            currentQuestionIndex: 0
        };

        ui.renderDuelGame(duelState);
        displayCurrentQuestion();
    };

    const displayCurrentQuestion = () => {
        if (duelState.currentQuestionIndex >= duelState.questions.length) {
            // Bu normalde olmamalı, sunucu son sorudan sonra yönlendirecek.
            console.error("Soru dizisinin sonuna ulaşıldı.");
            return;
        }
        const question = duelState.questions[duelState.currentQuestionIndex];
        ui.renderDuelQuestion(question, duelState.currentQuestionIndex, duelState.questions.length);
    };

    const handleAnswerSubmission = async (answer) => {
        ui.disableDuelOptions();

        const result = await api.call('duel_submit_answer', {
            duel_id: duelState.id,
            question_index: duelState.currentQuestionIndex,
            answer: answer
        });

        if (result.success) {
            const { is_correct, correct_answer, explanation, is_last_question, final_state } = result.data;

            if (is_correct) {
                duelState.myScore += 10;
            }

            ui.showDuelAnswerResult(answer, correct_answer, explanation, duelState.myScore);

            if (is_last_question) {
                // Son soru ise, backend zaten durumu güncelledi.
                // Özet ekranını göstermek için kısa bir gecikme.
                setTimeout(() => {
                    endDuel(final_state);
                }, 2000);
            } else {
                // Son soru değilse, "Sıradaki Soru" butonunu göster
                ui.toggleDuelNextButton(true);
            }
        } else {
            ui.showToast(result.message, 'error');
            // Hata durumunda arkadaş sayfasına dön
            setTimeout(() => {
                document.dispatchEvent(new Event('showMainView'));
            }, 2000);
        }
    };

    const nextQuestion = () => {
        duelState.currentQuestionIndex++;
        if (duelState.currentQuestionIndex < duelState.questions.length) {
            displayCurrentQuestion();
            ui.toggleDuelNextButton(false);
        }
    };

    const endDuel = async (finalState) => {
        // Düellonun en son halini sunucudan alıp göstermek daha güvenilir olabilir.
        // Ama şimdilik final_state'i kullanabiliriz.
        await friendsHandler.updateDuelsList(); // Arka planda listeyi güncelle

        ui.renderDuelSummary(duelState, finalState);
    };

    const addEventListeners = () => {
        dom.duelOptionsContainer?.addEventListener('click', (e) => {
            const btn = e.target.closest('.duel-option-button');
            if (btn && !btn.disabled) {
                handleAnswerSubmission(btn.dataset.answer);
            }
        });

        dom.duelNextQuestionBtn?.addEventListener('click', nextQuestion);

        dom.duelBackToFriendsBtn?.addEventListener('click', () => {
            document.dispatchEvent(new Event('showMainView'));
            ui.showTab('arkadaslar');
        });
    };

    return {
        init,
        startDuel
    };
})(); 