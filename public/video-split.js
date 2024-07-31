function createInputElement(id, name, isChecked = false) {
    const input = document.createElement('input');
    input.type = 'radio';
    input.id = id;
    input.name = name;
    if (isChecked) {
        input.checked = true;
    }

    return input;
}

function createLabelElement(forId, text) {
    const label = document.createElement('label');
    label.className = 'arsu_va_tab';
    label.setAttribute('for', forId);
    label.textContent = text;

    return label;
}

function createSectionElement(sectionObject) {
    const section = document.createElement('section');
    section.className = 'arsu_va_tab-panel';
    section.id = sectionObject.id;
    sectionObject.content.forEach(domId => {
        const domElement = document.getElementById(domId);
        section.appendChild(domElement);
    });

    return section;
}

function getSectionIds(answerType) {
    return Object.entries(arsu_va_options['answers'])
        .filter(([key, value]) => value === answerType)
        .map(([key, value]) => key);
}

function createTabStructure() {
    const aList = document.getElementById('a_list');

    // Create tab inputs and labels
    const tabs = [
        {id: 'arsu_va_tab-standard', label: arsu_va_options.lang.standard_answers_tab_label, checked: true},
        {id: 'arsu_va_tab-video', label: arsu_va_options.lang.video_answers_tab_label}
    ];

    tabs.forEach(tab => {
        aList.appendChild(createInputElement(tab.id, 'tabset', tab.checked));
        aList.appendChild(createLabelElement(tab.id, tab.label));
    });

    // Create tab content
    const tabContent = document.createElement('div');
    tabContent.className = 'arsu_va_tab-content';

    const sections = [
        {
            id: 'arsu_va_standardAnswersContainer',
            content: getSectionIds('standard')
        },
        {
            id: 'arsu_va_videoAnswersContainer',
            content: getSectionIds('video')
        }
    ];

    sections.forEach(section => {
        let node = createSectionElement(section);
        tabContent.appendChild(node);
    });

    aList.appendChild(tabContent);

    return aList;
}

createTabStructure();

window.addEventListener('load', () => {
    const observeDOM = (() => {
        const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

        return (obj, callback) => {
            if (!obj || obj.nodeType !== 1) {
                return;
            }

            if (MutationObserver) {
                const mutationObserver = new MutationObserver(callback);
                mutationObserver.observe(obj, {childList: true});

                return mutationObserver;
            } else if (window.addEventListener) { // browser support fallback
                obj.addEventListener('DOMNodeInserted', callback, false);
            }
        };
    })();

    const aList = document.getElementById('a_list');
    observeDOM(aList, m => {
        m.forEach(record => {
            record.addedNodes.forEach(node => {
                const parent = document.getElementById('arsu_va_' + node.dataset.answerType + 'AnswersContainer');
                parent.prepend(node);
                const tab = document.getElementById('arsu_va_tab-' + node.dataset.answerType);
                tab.checked = true;
            });
        });
    });
});
