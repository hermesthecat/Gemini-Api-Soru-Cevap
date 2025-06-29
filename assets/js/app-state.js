const appState = (() => {
    const state = {
        currentUser: null, // { id: 1, username: '...', role: '...', avatar: 'avatar1.svg' }
        csrfToken: null,   // CSRF koruması için
        difficulty: 'orta',
        currentCategory: null,
        soundEnabled: true,
        theme: 'light',
        leaderboardInterval: null,
        currentQuestionData: null,
        timerInterval: null,
        timeLeft: 30,
        lifelines: {
            fiftyFifty: 1,
            extraTime: 1,
            pass: 1
        }
    };

    return {
        get: (key) => state[key],
        set: (key, value) => {
            state[key] = value;
        },
        getAll: () => ({ ...state }) // Salt okunur bir kopya döndür
    };
})(); 