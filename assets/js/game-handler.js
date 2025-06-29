const game = {
    state: null,
    dom: null,
    ui: null,

    init(state, dom, ui) {
        this.state = state;
        this.dom = dom;
        this.ui = ui;
        this.addEventListeners();
    },

    updateLifelineUI() {
        const isTrueFalse = this.state.currentQuestionData && this.state.currentQuestionData.tip === 'dogru_yanlis';

        this.dom.lifelineFiftyFifty.disabled = this.state.lifelines.fiftyFifty <= 0 || isTrueFalse;
        this.dom.lifelineFiftyFifty.title = isTrueFalse ? "Bu soru tipinde kullanılamaz." : "50/50 Joker Hakkı";

        this.dom.lifelineExtraTime.disabled = this.state.lifelines.extraTime <= 0;

        const allUsed = this.state.lifelines.fiftyFifty <= 0 && this.state.lifelines.extraTime <= 0;
        this.dom.lifelineContainer.classList.toggle('hidden', allUsed);
    },

    displayQuestion(data) {
        this.state.currentQuestionData = data;
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
        this.state.timeLeft = 30;
        this.dom.countdown.textContent = this.state.timeLeft;
        this.dom.timerContainer.classList.remove('hidden');
        clearInterval(this.state.timerInterval);
        this.state.timerInterval = setInterval(async () => {
            this.state.timeLeft--;
            this.dom.countdown.textContent = this.state.timeLeft;
            if (this.state.timeLeft <= 0) {
                clearInterval(this.state.timerInterval);
                document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'timeout' } }));
                await this.handleAnswerSubmission('TIMEOUT');
            }
        }, 1000);
    },

    async handleAnswerSubmission(answer) {
        clearInterval(this.state.timerInterval);
        this.dom.timerContainer.classList.add('hidden');
        this.dom.lifelineContainer.classList.add('hidden');
        
        // Yükleme ekranı artık api.call içinde yönetiliyor.
        const result = await api.call('submit_answer', {
            answer: answer,
            kategori: this.state.currentQuestionData.kategori
        });

        if (result && result.success) {
            const { is_correct, correct_answer, explanation, new_achievements } = result.data;
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

            // Ana uygulamaya istatistikleri güncellemesi için haber ver
            document.dispatchEvent(new CustomEvent('answerSubmitted', { detail: { new_achievements } }));


            setTimeout(() => {
                this.dom.questionContainer.classList.add('hidden');
                this.dom.categorySelectionContainer.classList.remove('hidden');
            }, 3000);
        } else {
            // Hata api.call tarafından gösterildi, sadece arayüzü eski haline getirelim.
            if (result && result.message) {
                 this.ui.showToast(result.message, 'error');
            }
            this.dom.questionContainer.classList.add('hidden');
            this.dom.categorySelectionContainer.classList.remove('hidden');
        }
    },

    addEventListeners() {
        this.dom.difficultyButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.difficulty-button');
            if (btn) {
                this.state.difficulty = btn.dataset.zorluk;
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
                // Yükleme ekranı api.call içinde yönetiliyor.
                const result = await api.call('get_question', {
                    kategori: btn.dataset.kategori,
                    difficulty: this.state.difficulty
                });

                if (result && result.success) {
                    this.displayQuestion(result.data);
                } else if (result && result.message) {
                    // Soru formatı hatası gibi özel mesajları göster.
                    this.ui.showToast(result.message, 'error');
                }
                // Diğer tüm hatalar (network vs) zaten api.call tarafından gösterildi.
            }
        });

        this.dom.optionsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.option-button');
            if (btn) this.handleAnswerSubmission(btn.dataset.answer);
        });

        this.dom.lifelineFiftyFifty.addEventListener('click', () => {
            if (this.dom.lifelineFiftyFifty.disabled) return;

            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'correct' } }));
            this.state.lifelines.fiftyFifty--;
            this.updateLifelineUI();

            const correctAnswer = this.state.currentQuestionData.correct_answer;
            const options = Array.from(this.dom.optionsContainer.querySelectorAll('.option-button'));
            const wrongOptions = options.filter(btn => btn.dataset.answer !== correctAnswer);

            wrongOptions.sort(() => 0.5 - Math.random()); // Rastgele karıştır

            wrongOptions[0].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[0].disabled = true;
            wrongOptions[1].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[1].disabled = true;
        });

        this.dom.lifelineExtraTime.addEventListener('click', () => {
            if (this.dom.lifelineExtraTime.disabled) return;

            document.dispatchEvent(new CustomEvent('playSound', { detail: { sound: 'correct' } }));
            this.state.lifelines.extraTime--;
            this.updateLifelineUI();

            this.state.timeLeft += 15;
            this.dom.countdown.textContent = this.state.timeLeft;
        });
    }
}; 