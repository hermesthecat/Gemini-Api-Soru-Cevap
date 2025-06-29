const game = {
    state: null,
    dom: null,
    ui: null,

    init(dom) {
        this.dom = dom;
        this.populateCategories(appData.categories);
        this.addEventListeners();
    },

    populateCategories(categories) {
        if (!this.dom.categoryButtons) return;
        this.dom.categoryButtons.innerHTML = '';
        for (const [key, value] of Object.entries(categories)) {
            const button = document.createElement('button');
            button.className = `category-button bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md flex flex-col items-center justify-center text-center transition transform hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-${value.color}-500`;
            button.dataset.kategori = key;

            button.innerHTML = `
                <i class="fas ${value.icon} fa-3x text-${value.color}-500 mb-2"></i>
                <span class="font-semibold text-gray-700 dark:text-gray-200">${key.charAt(0).toUpperCase() + key.slice(1)}</span>
            `;
            this.dom.categoryButtons.appendChild(button);
        }
    },

    updateLifelineUI() {
        const lifelines = appState.get('lifelines');
        const isTrueFalse = appState.get('currentQuestionData')?.tip === 'dogru_yanlis';

        this.dom.lifelineFiftyFifty.disabled = lifelines.fiftyFifty <= 0 || isTrueFalse;
        this.dom.lifelineFiftyFifty.title = isTrueFalse ? "Bu soru tipinde kullanılamaz." : "50/50 Joker Hakkı";

        this.dom.lifelineExtraTime.disabled = lifelines.extraTime <= 0;
        this.dom.lifelinePass.disabled = lifelines.pass <= 0;

        const allUsed = lifelines.fiftyFifty <= 0 && lifelines.extraTime <= 0 && lifelines.pass <= 0;
        this.dom.lifelineContainer.classList.toggle('hidden', allUsed);
    },

    displayQuestion(data) {
        appState.set('currentQuestionData', data);
        this.dom.questionContainer.classList.remove('hidden');
        this.dom.categorySelectionContainer.classList.add('hidden');
        this.dom.explanationContainer.classList.add('hidden');

        this.dom.questionCategory.textContent = `${data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1)} - ${data.difficulty}`;
        this.dom.questionText.textContent = data.question;
        this.dom.optionsContainer.innerHTML = '';

        if (data.tip === 'dogru_yanlis') {
            this.dom.optionsContainer.className = 'grid grid-cols-1 gap-4 items-center';
            ['Doğru', 'Yanlış'].forEach(opt => {
                const btn = document.createElement('button');
                btn.className = 'option-button p-4 text-center rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700 w-full md:w-1/2 mx-auto';
                btn.dataset.answer = opt;
                btn.textContent = opt;
                this.dom.optionsContainer.appendChild(btn);
            });
        } else {
            this.dom.optionsContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 items-center';
            Object.entries(data.siklar).forEach(([key, value]) => {
                const btn = document.createElement('button');
                btn.className = 'option-button p-4 text-left rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700';
                btn.dataset.answer = key;
                btn.innerHTML = `<span class="font-semibold">${key}</span>) ${value}`;
                this.dom.optionsContainer.appendChild(btn);
            });
        }
        this.updateLifelineUI();
        this.startTimer();
    },

    startTimer() {
        appState.set('timeLeft', 30);
        this.dom.countdown.textContent = appState.get('timeLeft');
        this.dom.timerContainer.classList.remove('hidden');
        clearInterval(appState.get('timerInterval'));

        const timerInterval = setInterval(async () => {
            let timeLeft = appState.get('timeLeft');
            timeLeft--;
            appState.set('timeLeft', timeLeft);
            this.dom.countdown.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(appState.get('timerInterval'));
                document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'timeout' } }));
                await this.handleAnswerSubmission('TIMEOUT');
            }
        }, 1000);
        appState.set('timerInterval', timerInterval);
    },

    async handleAnswerSubmission(answer) {
        clearInterval(appState.get('timerInterval'));
        this.dom.timerContainer.classList.add('hidden');
        this.dom.lifelineContainer.classList.add('hidden');

        const result = await api.call('submit_answer', {
            answer: answer,
            kategori: appState.get('currentQuestionData').kategori
        });

        if (result && result.success) {
            const { is_correct, correct_answer, explanation, new_achievements, completed_quests } = result.data;
            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: is_correct ? 'correct' : 'incorrect' } }));

            this.dom.optionsContainer.querySelectorAll('.option-button').forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.answer === correct_answer) {
                    btn.classList.add('bg-green-200', 'dark:bg-green-500', 'font-semibold');
                } else if (btn.dataset.answer === answer && !is_correct) {
                    btn.classList.add('bg-red-200', 'dark:bg-red-500', 'font-semibold');
                }
            });

            this.dom.explanationText.textContent = explanation;
            this.dom.explanationContainer.classList.remove('hidden');

            document.dispatchEvent(new CustomEvent('answerSubmitted', { detail: { new_achievements, completed_quests } }));

            setTimeout(() => {
                this.dom.questionContainer.classList.add('hidden');
                this.dom.categorySelectionContainer.classList.remove('hidden');
            }, 3000);
        } else {
            if (result && result.message) {
                this.ui.showToast(result.message, 'error');
            }
            this.dom.questionContainer.classList.add('hidden');
            this.dom.categorySelectionContainer.classList.remove('hidden');
        }
    },

    async getNewQuestion() {
        const currentQuestion = appState.get('currentQuestionData');
        if (!currentQuestion) return;

        clearInterval(appState.get('timerInterval'));

        const result = await api.call('get_question', {
            kategori: currentQuestion.kategori,
            difficulty: currentQuestion.difficulty
        });
        if (result && result.success) {
            this.displayQuestion(result.data);
        } else if (result && result.message) {
            this.ui.showToast(result.message, 'error');
            this.dom.questionContainer.classList.add('hidden');
            this.dom.categorySelectionContainer.classList.remove('hidden');
        }
    },

    addEventListeners() {
        this.dom.difficultyButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.difficulty-button');
            if (btn) {
                appState.set('difficulty', btn.dataset.zorluk);
                this.dom.difficultyButtons.querySelectorAll('.difficulty-button').forEach(b => {
                    b.classList.remove('bg-blue-500', 'text-white', 'font-semibold');
                    b.classList.add('bg-gray-200', 'dark:bg-gray-700');
                });
                btn.classList.add('bg-blue-500', 'text-white', 'font-semibold');
                btn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
            }
        });

        this.dom.categoryButtons.addEventListener('click', async (e) => {
            const btn = e.target.closest('.category-button');
            if (btn) {
                const result = await api.call('get_question', {
                    kategori: btn.dataset.kategori,
                    difficulty: appState.get('difficulty')
                });

                if (result && result.success) {
                    this.displayQuestion(result.data);
                } else if (result && result.message) {
                    this.ui.showToast(result.message, 'error');
                }
            }
        });

        this.dom.optionsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.option-button');
            if (btn) this.handleAnswerSubmission(btn.dataset.answer);
        });

        this.dom.lifelineFiftyFifty.addEventListener('click', async () => {
            if (this.dom.lifelineFiftyFifty.disabled) return;

            const result = await api.call('use_lifeline', { type: 'fiftyFifty' });
            if (!result.success) return;

            appState.set('lifelines', result.data.lifelines);
            this.updateLifelineUI();
            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'correct' } }));

            const correctAnswer = appState.get('currentQuestionData').correct_answer;
            const options = Array.from(this.dom.optionsContainer.querySelectorAll('.option-button'));
            const wrongOptions = options.filter(btn => btn.dataset.answer !== correctAnswer);

            wrongOptions.sort(() => 0.5 - Math.random());

            wrongOptions[0].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[0].disabled = true;
            wrongOptions[1].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[1].disabled = true;
        });

        this.dom.lifelineExtraTime.addEventListener('click', async () => {
            if (this.dom.lifelineExtraTime.disabled) return;

            const result = await api.call('use_lifeline', { type: 'extraTime' });
            if (!result.success) return;

            appState.set('lifelines', result.data.lifelines);
            this.updateLifelineUI();
            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'correct' } }));

            let timeLeft = appState.get('timeLeft');
            timeLeft += 15;
            appState.set('timeLeft', timeLeft);
            this.dom.countdown.textContent = timeLeft;
        });

        this.dom.lifelinePass.addEventListener('click', async () => {
            if (this.dom.lifelinePass.disabled) return;

            const result = await api.call('use_lifeline', { type: 'pass' });
            if (!result.success) return;

            appState.set('lifelines', result.data.lifelines);
            this.updateLifelineUI();
            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'correct' } }));

            this.getNewQuestion();
        });
    }
};