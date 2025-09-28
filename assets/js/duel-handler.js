const duelHandler = (() => {
    let dom = {};
    let duelState = {}; // Mevcut düello ile ilgili tüm verileri tutacak

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    // Helper methods to get modules with fallback
    const showView = (viewId) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showView(viewId);
        } else if (window.ui && window.ui.showView) {
            window.ui.showView(viewId);
        }
    };

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

    const showTab = (tabId) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showTab(tabId);
        } else if (window.ui && window.ui.showTab) {
            window.ui.showTab(tabId);
        }
    };

    const getUIQuestions = () => {
        const UIQuestions = ModuleLoader?.getModule('UIQuestions');
        if (UIQuestions) {
            return UIQuestions;
        } else if (window.ui) {
            return window.ui;
        }
        return null;
    };

    const getUISocial = () => {
        const UISocial = ModuleLoader?.getModule('UISocial');
        if (UISocial) {
            return UISocial;
        } else if (window.ui) {
            return window.ui;
        }
        return null;
    };

    const startDuel = async (duelId) => {
        const result = await api.call('duel_start_game', { duel_id: duelId });
        if (result.success) {
            setupDuel(result.data);
            showView('duel-game-view');
        } else {
            showToast(result.message, 'error');
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

        const uiSocial = getUISocial();
        if (uiSocial && uiSocial.renderDuelGame) {
            uiSocial.renderDuelGame(duelState);
        }
        displayCurrentQuestion();
    };

    const displayCurrentQuestion = () => {
        if (duelState.currentQuestionIndex >= duelState.questions.length) {
            // Bu normalde olmamalı, sunucu son sorudan sonra yönlendirecek.
            return;
        }
        const question = duelState.questions[duelState.currentQuestionIndex];
        const uiQuestions = getUIQuestions();
        if (uiQuestions && uiQuestions.renderDuelQuestion) {
            uiQuestions.renderDuelQuestion(question, duelState.currentQuestionIndex, duelState.questions.length);
        }
    };

    const handleAnswerSubmission = async (answer) => {
        const uiQuestions = getUIQuestions();
        if (uiQuestions && uiQuestions.disableDuelOptions) {
            uiQuestions.disableDuelOptions();
        }

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

            const uiQuestions = getUIQuestions();
            if (uiQuestions && uiQuestions.showDuelAnswerResult) {
                uiQuestions.showDuelAnswerResult(answer, correct_answer, explanation, duelState.myScore);
            }

            if (is_last_question) {
                // Son soru ise, backend zaten durumu güncelledi.
                // Özet ekranını göstermek için kısa bir gecikme.
                setTimeout(() => {
                    endDuel(final_state);
                }, 2000);
            } else {
                // Son soru değilse, "Sıradaki Soru" butonunu göster
                const uiQuestions = getUIQuestions();
                if (uiQuestions && uiQuestions.toggleDuelNextButton) {
                    uiQuestions.toggleDuelNextButton(true);
                }
            }
        } else {
            showToast(result.message, 'error');
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
            const uiQuestions = getUIQuestions();
            if (uiQuestions && uiQuestions.toggleDuelNextButton) {
                uiQuestions.toggleDuelNextButton(false);
            }
        }
    };

    const endDuel = async (finalState) => {
        // Düellonun en son halini sunucudan alıp göstermek daha güvenilir olabilir.
        // Ama şimdilik final_state'i kullanabiliriz.
        await friendsHandler.updateDuelsList(); // Arka planda listeyi güncelle

        const uiSocial = getUISocial();
        if (uiSocial && uiSocial.renderDuelSummary) {
            uiSocial.renderDuelSummary(duelState, finalState);
        }
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
            showTab('arkadaslar');
        });
    };

    return {
        init,
        startDuel
    };
})(); 