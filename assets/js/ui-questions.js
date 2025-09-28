/**
 * UI Questions Module - Question Management System
 *
 * This module handles all question-related UI functionality including
 * question rendering, answer processing, rating system, and admin
 * question management features.
 *
 * Phase 3 of ui-handler.js modularization - Independent Feature Modules
 * Created: 2025-09-28
 */

const UIQuestions = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add question-specific DOM elements
        dom.duelGameProgress = document.getElementById('duel-game-progress');
        dom.duelQuestionText = document.getElementById('duel-question-text');
        dom.duelOptionsContainer = document.getElementById('duel-options-container');
        dom.duelExplanationContainer = document.getElementById('duel-explanation-container');
        dom.duelExplanationText = document.getElementById('duel-explanation-text');
        dom.duelMyScore = document.getElementById('duel-my-score');

        console.log('UIQuestions initialized');
    };

    // Render duel question (extracted from ui-handler.js)
    const renderDuelQuestion = (question, index, total) => {
        if (!dom.duelGameProgress || !dom.duelQuestionText || !dom.duelOptionsContainer) return;

        dom.duelGameProgress.textContent = `Soru ${index + 1} / ${total}`;
        dom.duelQuestionText.textContent = question.soru;
        dom.duelOptionsContainer.innerHTML = '';
        dom.duelExplanationContainer?.classList.add('hidden');

        const createOptionButton = (text, answer) => {
            const btn = document.createElement('button');
            btn.className = 'duel-option-button p-4 text-left rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors';
            btn.dataset.answer = answer;
            btn.innerHTML = text;
            return btn;
        };

        if (question.tip === 'dogru_yanlis') {
            dom.duelOptionsContainer.className = 'grid grid-cols-1 gap-4 items-center';
            dom.duelOptionsContainer.appendChild(createOptionButton('Doğru', 'dogru'));
            dom.duelOptionsContainer.appendChild(createOptionButton('Yanlış', 'yanlis'));
        } else {
            dom.duelOptionsContainer.className = 'grid grid-cols-1 gap-4';
            ['A', 'B', 'C', 'D'].forEach(option => {
                if (question[option.toLowerCase()]) {
                    dom.duelOptionsContainer.appendChild(createOptionButton(question[option.toLowerCase()], option.toLowerCase()));
                }
            });
        }
    };

    // Show duel answer result with visual feedback
    const showDuelAnswerResult = (userAnswer, correctAnswer, explanation, myScore) => {
        if (dom.duelMyScore) {
            dom.duelMyScore.textContent = myScore;
        }

        // Highlight correct and user answers
        dom.duelOptionsContainer?.querySelectorAll('.duel-option-button').forEach(btn => {
            if (btn.dataset.answer === correctAnswer) {
                btn.classList.add('bg-green-200', 'dark:bg-green-500', 'font-semibold');
            } else if (btn.dataset.answer === userAnswer) {
                btn.classList.add('bg-red-200', 'dark:bg-red-500', 'font-semibold');
            }
        });

        // Show explanation
        if (dom.duelExplanationText && dom.duelExplanationContainer) {
            dom.duelExplanationText.textContent = explanation;
            dom.duelExplanationContainer.classList.remove('hidden');
        }
    };

    // Disable duel options (prevent multiple clicks)
    const disableDuelOptions = () => {
        dom.duelOptionsContainer?.querySelectorAll('.duel-option-button').forEach(btn => {
            btn.disabled = true;
            btn.classList.add('cursor-not-allowed', 'opacity-60');
        });
    };

    // Toggle duel next button visibility
    const toggleDuelNextButton = (show) => {
        const nextBtn = document.getElementById('duel-next-question-btn');
        if (nextBtn) {
            nextBtn.classList.toggle('hidden', !show);
        }
    };

    // Render question statistics (for admin)
    const renderQuestionStats = (statsData) => {
        const container = document.getElementById('question-stats-container');
        if (!container || !statsData) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Toplam Soru</h3>
                            <p class="text-2xl font-bold text-blue-600">${statsData.total_questions || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-star text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Ortalama Puan</h3>
                            <p class="text-2xl font-bold text-yellow-600">${statsData.average_rating || 0}/5</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                            <i class="fas fa-flag text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Şikayet Edilen</h3>
                            <p class="text-2xl font-bold text-red-600">${statsData.reported_questions || 0}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${statsData.category_stats ? `
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Kategori Bazında İstatistikler</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Soru Sayısı</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ortalama Puan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                ${statsData.category_stats.map(cat => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">${cat.category_name}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${cat.question_count}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${cat.average_rating}/5</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
        `;
    };

    // Render reported questions for admin review
    const renderReportedQuestions = (questions) => {
        const container = document.getElementById('reported-questions-container');
        if (!container) return;

        if (!questions || questions.length === 0) {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Şikayet edilen soru bulunmuyor.</p>';
            return;
        }

        container.innerHTML = questions.map(question => `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md border-l-4 border-red-500">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Soru ID: ${question.id}</h4>
                        <p class="text-gray-700 dark:text-gray-300 mb-2">${question.soru}</p>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <p>Kategori: ${question.category_name}</p>
                            <p>Zorluk: ${question.difficulty}</p>
                            <p>Şikayet Sayısı: ${question.report_count}</p>
                        </div>
                    </div>
                    <div class="flex space-x-2 ml-4">
                        <button class="review-question-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
                                data-question-id="${question.id}">
                            <i class="fas fa-eye mr-1"></i> İncele
                        </button>
                    </div>
                </div>
                ${question.reports && question.reports.length > 0 ? `
                    <div class="border-t pt-4">
                        <h5 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Şikayet Detayları:</h5>
                        <div class="space-y-2">
                            ${question.reports.map(report => `
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                    <p class="text-sm"><strong>Sebep:</strong> ${report.reason}</p>
                                    ${report.feedback ? `<p class="text-sm"><strong>Açıklama:</strong> ${report.feedback}</p>` : ''}
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kullanıcı: ${report.username} - ${report.created_at}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `).join('');
    };

    // Show question review modal
    const showQuestionReviewModal = (question) => {
        const modal = document.getElementById('question-review-modal');
        if (!modal) return;

        const questionText = modal.querySelector('#review-question-text');
        const questionOptions = modal.querySelector('#review-question-options');
        const questionCorrect = modal.querySelector('#review-question-correct');
        const questionExplanation = modal.querySelector('#review-question-explanation');

        if (questionText) questionText.textContent = question.soru;

        if (questionOptions) {
            if (question.tip === 'dogru_yanlis') {
                questionOptions.innerHTML = '<p class="text-sm text-gray-600 dark:text-gray-400">Doğru/Yanlış sorusu</p>';
            } else {
                questionOptions.innerHTML = `
                    <div class="space-y-2">
                        <p><strong>A:</strong> ${question.a || 'N/A'}</p>
                        <p><strong>B:</strong> ${question.b || 'N/A'}</p>
                        <p><strong>C:</strong> ${question.c || 'N/A'}</p>
                        <p><strong>D:</strong> ${question.d || 'N/A'}</p>
                    </div>
                `;
            }
        }

        if (questionCorrect) questionCorrect.textContent = question.dogru_cevap;
        if (questionExplanation) questionExplanation.textContent = question.aciklama || 'Açıklama yok';

        modal.classList.remove('hidden');
    };

    // Hide question review modal
    const hideQuestionReviewModal = () => {
        const modal = document.getElementById('question-review-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    // Question rating utilities
    const initQuestionRating = () => {
        const ratingStars = document.querySelectorAll('.rating-star');
        const submitBtn = document.getElementById('rating-submit-btn');
        let selectedRating = 0;

        ratingStars.forEach(star => {
            star.addEventListener('click', () => {
                selectedRating = parseInt(star.dataset.rating);
                updateStarDisplay(selectedRating);
                if (submitBtn) submitBtn.disabled = false;
            });
        });

        function updateStarDisplay(rating) {
            ratingStars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('text-yellow-400');
                    star.classList.remove('text-gray-300');
                } else {
                    star.classList.add('text-gray-300');
                    star.classList.remove('text-yellow-400');
                }
            });
        }

        return { getSelectedRating: () => selectedRating };
    };

    // Public API
    return {
        init,

        // Duel question functions
        renderDuelQuestion,
        showDuelAnswerResult,
        disableDuelOptions,
        toggleDuelNextButton,

        // Admin question functions
        renderQuestionStats,
        renderReportedQuestions,
        showQuestionReviewModal,
        hideQuestionReviewModal,

        // Question rating
        initQuestionRating
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIQuestions', UIQuestions);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIQuestions', UIQuestions);
                }
            }, 100);
        });
    }
}