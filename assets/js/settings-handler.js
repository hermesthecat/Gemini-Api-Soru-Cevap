const settingsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        
        // Kayıtlı ayarları yükle
        const savedTheme = localStorage.getItem('theme') || 'light';
        const soundEnabled = localStorage.getItem('soundEnabled') !== 'false';
        
        appState.set('theme', savedTheme);
        appState.set('soundEnabled', soundEnabled);
        
        applyTheme(savedTheme);
        applySoundSetting(soundEnabled);
        
        addEventListeners();
    };

    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            dom.themeToggleDarkIcon?.classList.add('hidden');
            dom.themeToggleLightIcon?.classList.remove('hidden');
        } else {
            document.documentElement.classList.remove('dark');
            dom.themeToggleDarkIcon?.classList.remove('hidden');
            dom.themeToggleLightIcon?.classList.add('hidden');
        }
    };

    const applySoundSetting = (enabled) => {
        dom.soundOnIcon?.classList.toggle('hidden', !enabled);
        dom.soundOffIcon?.classList.toggle('hidden', enabled);
    };

    const toggleTheme = () => {
        const currentTheme = appState.get('theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        appState.set('theme', newTheme);
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    };

    const toggleSound = () => {
        const newSoundState = !appState.get('soundEnabled');
        appState.set('soundEnabled', newSoundState);
        localStorage.setItem('soundEnabled', newSoundState);
        applySoundSetting(newSoundState);
    };

    const addEventListeners = () => {
        dom.themeToggle?.addEventListener('click', toggleTheme);
        dom.soundToggle?.addEventListener('click', toggleSound);
    };

    return { init };
})(); 