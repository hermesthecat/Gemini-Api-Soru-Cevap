const game = {
    state: null,
    dom: null,
    ui: null,

    init(dom) {
        this.dom = dom;
        // Kategoriler App.loadCategories() tarafından yüklenecek
        this.addEventListeners();
    },

    // Helper method to get UICore module with fallback
    showToast(message, type) {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (window.ui && window.ui.showToast) {
            // Fallback to legacy ui-handler
            window.ui.showToast(message, type);
        } else {
            // Final fallback to console
            console.log(`[${type}] ${message}`);
        }
    },

    populateCategories() {
        if (!this.dom.categoryButtons) return;

        const categories = appState.get('categories');
        if (!categories) return;

        this.dom.categoryButtons.innerHTML = '';
        categories.forEach(category => {
            const button = document.createElement('button');
            button.className = `category-button bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md flex flex-col items-center justify-center text-center transition transform hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-${category.color}-500`;
            button.dataset.kategori = category.category_key;

            button.innerHTML = `
                <i class="fas ${category.icon} fa-3x text-${category.color}-500 mb-2"></i>
                <span class="font-semibold text-gray-700 dark:text-gray-200">${category.category_name}</span>
            `;
            this.dom.categoryButtons.appendChild(button);
        });
    },

    updateLifelineUI() {
        const lifelines = appState.get('lifelines');
        const isTrueFalse = appState.get('currentQuestionData')?.tip === 'dogru_yanlis';

        if (this.dom.lifelineFiftyFifty) {
            this.dom.lifelineFiftyFifty.disabled = lifelines.fiftyFifty <= 0 || isTrueFalse;
            this.dom.lifelineFiftyFifty.title = isTrueFalse ? "Bu soru tipinde kullanılamaz." : "50/50 Joker Hakkı";
        }

        if (this.dom.lifelineExtraTime) {
            this.dom.lifelineExtraTime.disabled = lifelines.extraTime <= 0;
        }

        if (this.dom.lifelinePass) {
            this.dom.lifelinePass.disabled = lifelines.pass <= 0;
        }

        if (this.dom.lifelineContainer) {
            const allUsed = lifelines.fiftyFifty <= 0 && lifelines.extraTime <= 0 && lifelines.pass <= 0;
            this.dom.lifelineContainer.classList.toggle('hidden', allUsed);
        }
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

            // Show rating modal after a short delay (only 20% chance to avoid modal fatigue)
            if (Math.random() < 0.2) {
                setTimeout(() => {
                    const currentQuestionData = appState.get('currentQuestionData');
                    if (currentQuestionData && currentQuestionData.id) {
                        this.showQuestionRatingModal(currentQuestionData);
                    }
                }, 2000);
            }

            setTimeout(() => {
                this.dom.questionContainer.classList.add('hidden');
                this.dom.categorySelectionContainer.classList.remove('hidden');
            }, 3000);
        } else {
            if (result && result.message) {
                this.showToast(result.message, 'error');
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
            if (window.ui && window.ui.showToast) {
                window.ui.showToast(result.message, 'error');
            }
            this.dom.questionContainer.classList.add('hidden');
            this.dom.categorySelectionContainer.classList.remove('hidden');
        }
    },

    addEventListeners() {
        if (this.dom.difficultyButtons) {
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
        }

        if (this.dom.categoryButtons) {
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
                    if (window.ui && window.ui.showToast) {
                        window.ui.showToast(result.message, 'error');
                    }
                }
            }
            });
        }

        if (this.dom.optionsContainer) {
            this.dom.optionsContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('.option-button');
                if (btn) this.handleAnswerSubmission(btn.dataset.answer);
            });
        }

        if (this.dom.lifelineFiftyFifty) {
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
        }

        if (this.dom.lifelineExtraTime) {
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
        }

        if (this.dom.lifelinePass) {
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

        // Question Rating Modal Event Listeners
        const ratingModal = document.getElementById('question-rating-modal');
        const ratingModalCloseBtn = document.getElementById('question-rating-modal-close-btn');
        const ratingStars = document.querySelectorAll('.rating-star');
        const ratingSubmitBtn = document.getElementById('rating-submit-btn');
        const ratingReportBtn = document.getElementById('rating-report-btn');
        const reportSection = document.getElementById('report-section');
        const reportReason = document.getElementById('report-reason');

        if (ratingModalCloseBtn) {
            ratingModalCloseBtn.addEventListener('click', () => {
                this.hideQuestionRatingModal();
            });
        }

        if (ratingModal) {
            ratingModal.addEventListener('click', (e) => {
                if (e.target === ratingModal) {
                    this.hideQuestionRatingModal();
                }
            });
        }

        // Star rating logic
        if (ratingStars.length > 0) {
            ratingStars.forEach(star => {
                star.addEventListener('click', (e) => {
                    const rating = parseInt(e.currentTarget.dataset.rating);
                    this.setStarRating(rating);
                });

                star.addEventListener('mouseenter', (e) => {
                    const rating = parseInt(e.currentTarget.dataset.rating);
                    this.highlightStars(rating);
                });
            });

            // Reset stars on mouse leave
            const starsContainer = document.getElementById('rating-stars');
            if (starsContainer) {
                starsContainer.addEventListener('mouseleave', () => {
                    const currentRating = this.currentRating || 0;
                    this.highlightStars(currentRating);
                });
            }
        }

        if (ratingSubmitBtn) {
            ratingSubmitBtn.addEventListener('click', () => {
                this.submitRating();
            });
        }

        if (ratingReportBtn) {
            ratingReportBtn.addEventListener('click', () => {
                this.toggleReportMode();
            });
        }
    },

    // Question Rating Modal Methods
    showQuestionRatingModal(questionData) {
        const modal = document.getElementById('question-rating-modal');
        const modalContent = document.getElementById('question-rating-modal-content');

        if (!modal) return;

        // Store current question data
        this.currentQuestionForRating = questionData;
        this.currentRating = 0;
        this.isReportMode = false;

        // Reset modal state
        this.resetRatingModal();

        // Show modal with animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            if (modalContent) {
                modalContent.classList.remove('scale-95');
            }
        }, 10);
    },

    hideQuestionRatingModal() {
        const modal = document.getElementById('question-rating-modal');
        const modalContent = document.getElementById('question-rating-modal-content');

        if (!modal) return;

        modal.classList.add('opacity-0');
        if (modalContent) {
            modalContent.classList.add('scale-95');
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            this.resetRatingModal();
        }, 300);
    },

    resetRatingModal() {
        this.currentRating = 0;
        this.isReportMode = false;

        // Reset stars
        this.highlightStars(0);

        // Reset form elements
        const feedback = document.getElementById('rating-feedback');
        const reportSection = document.getElementById('report-section');
        const reportReason = document.getElementById('report-reason');
        const submitBtn = document.getElementById('rating-submit-btn');
        const reportBtn = document.getElementById('rating-report-btn');

        if (feedback) feedback.value = '';
        if (reportSection) reportSection.classList.add('hidden');
        if (reportReason) reportReason.value = '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Gönder';
        }
        if (reportBtn) {
            reportBtn.innerHTML = '<i class="fas fa-flag mr-2"></i>Şikayet Et';
            reportBtn.classList.remove('bg-gray-500');
            reportBtn.classList.add('bg-red-500');
        }
    },

    setStarRating(rating) {
        this.currentRating = rating;
        this.highlightStars(rating);

        // Enable submit button
        const submitBtn = document.getElementById('rating-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    },

    highlightStars(rating) {
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            const starIcon = star.querySelector('i');
            if (starIcon) {
                if (index < rating) {
                    starIcon.classList.remove('text-gray-300');
                    starIcon.classList.add('text-yellow-400');
                } else {
                    starIcon.classList.remove('text-yellow-400');
                    starIcon.classList.add('text-gray-300');
                }
            }
        });
    },

    toggleReportMode() {
        const reportSection = document.getElementById('report-section');
        const reportBtn = document.getElementById('rating-report-btn');
        const submitBtn = document.getElementById('rating-submit-btn');

        if (!this.isReportMode) {
            // Switch to report mode
            this.isReportMode = true;
            if (reportSection) reportSection.classList.remove('hidden');
            if (reportBtn) {
                reportBtn.innerHTML = '<i class="fas fa-star mr-2"></i>Puan Ver';
                reportBtn.classList.remove('bg-red-500');
                reportBtn.classList.add('bg-gray-500');
            }
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-flag mr-2"></i>Şikayet Gönder';
                submitBtn.disabled = false;
            }
        } else {
            // Switch back to rating mode
            this.isReportMode = false;
            if (reportSection) reportSection.classList.add('hidden');
            if (reportBtn) {
                reportBtn.innerHTML = '<i class="fas fa-flag mr-2"></i>Şikayet Et';
                reportBtn.classList.remove('bg-gray-500');
                reportBtn.classList.add('bg-red-500');
            }
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Gönder';
                submitBtn.disabled = this.currentRating === 0;
            }
        }
    },

    async submitRating() {
        if (!this.currentQuestionForRating) {
            console.error('No question data available for rating');
            return;
        }

        const feedback = document.getElementById('rating-feedback')?.value || '';
        const submitBtn = document.getElementById('rating-submit-btn');

        // Disable button during submission
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gönderiliyor...';
        }

        try {
            let result;

            if (this.isReportMode) {
                // Submit as report
                result = await this.reportQuestion();
            } else {
                // Submit as rating
                if (this.currentRating === 0) {
                    console.error('No rating selected');
                    return;
                }

                result = await api.call('submit_question_rating', {
                    question_id: this.currentQuestionForRating.id,
                    rating: this.currentRating,
                    feedback: feedback
                });
            }

            if (result && result.success) {
                this.showToast(result.message || 'Değerlendirmeniz kaydedildi!', 'success');
                this.hideQuestionRatingModal();
            } else {
                this.showToast(result?.message || 'Bir hata oluştu.', 'error');
            }
        } catch (error) {
            console.error('Rating submission error:', error);
            this.showToast('Bağlantı hatası oluştu.', 'error');
        } finally {
            // Re-enable button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = this.isReportMode ?
                    '<i class="fas fa-flag mr-2"></i>Şikayet Gönder' :
                    '<i class="fas fa-check mr-2"></i>Gönder';
            }
        }
    },

    async reportQuestion() {
        const reportReason = document.getElementById('report-reason')?.value;
        const feedback = document.getElementById('rating-feedback')?.value || '';

        if (!reportReason) {
            this.showToast('Lütfen şikayet sebebini seçin.', 'error');
            return { success: false };
        }

        return await api.call('report_question', {
            question_id: this.currentQuestionForRating.id,
            report_reason: reportReason,
            feedback: feedback
        });
    }
};