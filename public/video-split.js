// === Video/Standard Answer Tab Splitter ===

(function() {
    const aList = document.getElementById('a_list');
    if (!aList || typeof arsu_va_options === 'undefined') return;

    const answers = arsu_va_options.answers || {};
    const lang = arsu_va_options.lang || {};

    // Group answer DOM IDs by type
    const groups = { standard: [], video: [] };
    Object.entries(answers).forEach(([domId, type]) => {
        if (groups[type]) groups[type].push(domId);
    });

    const hasStandard = groups.standard.length > 0;
    const hasVideo = groups.video.length > 0;

    // Only one type (or no answers) — leave DOM untouched, no tabs needed
    if (!hasStandard || !hasVideo) return;

    // === Build tab UI (both types present) ===

    const tabs = [
        { type: 'standard', label: lang.standard_answers_tab_label || 'Standard answers' },
        { type: 'video',    label: lang.video_answers_tab_label || 'Video answers' }
    ];

    const tabBar = document.createElement('div');
    tabBar.className = 'arsu_va_tab-bar';

    tabs.forEach((tab, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'arsu_va_tab' + (i === 0 ? ' arsu_va_active' : '');
        btn.textContent = tab.label;
        btn.dataset.type = tab.type;
        btn.addEventListener('click', () => switchTab(tab.type));
        tabBar.appendChild(btn);
    });

    // Insert tab bar before answers
    aList.insertBefore(tabBar, aList.firstChild);

    // Wrap answers into sections
    const tabContent = document.createElement('div');
    tabContent.className = 'arsu_va_tab-content';

    tabs.forEach((tab, i) => {
        const section = document.createElement('section');
        section.className = 'arsu_va_tab-panel' + (i === 0 ? ' arsu_va_active' : '');
        section.id = 'arsu_va_' + tab.type + 'AnswersContainer';

        groups[tab.type].forEach(domId => {
            const el = document.getElementById(domId);
            if (el) section.appendChild(el);
        });

        tabContent.appendChild(section);
    });

    aList.appendChild(tabContent);

    function switchTab(type) {
        tabBar.querySelectorAll('.arsu_va_tab').forEach(btn => {
            btn.classList.toggle('arsu_va_active', btn.dataset.type === type);
        });
        tabContent.querySelectorAll('.arsu_va_tab-panel').forEach(panel => {
            panel.classList.toggle('arsu_va_active', panel.id === 'arsu_va_' + type + 'AnswersContainer');
        });
    }

    // === Observe for dynamically added answers ===
    const observer = new MutationObserver(mutations => {
        mutations.forEach(record => {
            record.addedNodes.forEach(node => {
                if (node.nodeType === 1 && node.dataset && node.dataset.answerType) {
                    const type = node.dataset.answerType;
                    const container = document.getElementById('arsu_va_' + type + 'AnswersContainer');
                    if (container) {
                        container.prepend(node);
                        switchTab(type);
                    }
                }
            });
        });
    });
    observer.observe(aList, { childList: true });
})();