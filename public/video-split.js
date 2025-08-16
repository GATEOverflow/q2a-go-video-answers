// === Plugin configuration ===
const arsu_va_config = {
    // Set to true to always show tabs, even when only one answer type is present
    showSingleTab: false
};

// === Helper Functions ===

function createInputElement(id, name, isChecked = false) {
    const input = document.createElement('input');
    input.type = 'radio';
    input.id = id;
    input.name = name;
    if (isChecked) input.checked = true;
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
        if (domElement) {
            section.appendChild(domElement);
        }
    });

    return section;
}

function getSectionIds(answerType) {
    return Object.entries(arsu_va_options['answers'])
        .filter(([_, value]) => value === answerType)
        .map(([key, _]) => key);
}

// === Main Tab Logic ===

function createTabStructure() {
    const aList = document.getElementById('a_list');
	
	// Create tab inputs and labels
    const contentTypes = [
        { type: 'standard', label: arsu_va_options.lang.standard_answers_tab_label },
        { type: 'video', label: arsu_va_options.lang.video_answers_tab_label }
    ];

    // Determine which tabs have actual content
    const availableTabs = contentTypes
        .map(tab => {
            const ids = getSectionIds(tab.type);
            return ids.length > 0 ? { ...tab, content: ids } : null;
        })
        .filter(Boolean);

    // Case: no answers at all
    if (availableTabs.length === 0) {
        const message = document.createElement('p');
        message.textContent = arsu_va_options.lang.no_answers_message || "No answers available.";
        aList.appendChild(message);
        return;
    }

    // Case: only one answer type and tabs disabled for that
    const isSingleTab = availableTabs.length === 1;

    // Add inputs and conditionally add labels
    availableTabs.forEach((tab, index) => {
        aList.appendChild(createInputElement(`arsu_va_tab-${tab.type}`, 'tabset', index === 0));

        const shouldShowLabel = !isSingleTab || arsu_va_config.showSingleTab;
        if (shouldShowLabel) {
            aList.appendChild(createLabelElement(`arsu_va_tab-${tab.type}`, tab.label));
        }
    });

    // Create tab content
    const tabContent = document.createElement('div');
    tabContent.className = 'arsu_va_tab-content';

    availableTabs.forEach(tab => {
        const section = createSectionElement({
            id: `arsu_va_${tab.type}AnswersContainer`,
            content: tab.content
        });
        tabContent.appendChild(section);
    });

    aList.appendChild(tabContent);
}

createTabStructure();

// === Observe for Dynamic Additions ===

window.addEventListener('load', () => {
    const observeDOM = (() => {
        const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

        return (obj, callback) => {
            if (!obj || obj.nodeType !== 1) return;

            if (MutationObserver) {
                const mutationObserver = new MutationObserver(callback);
                mutationObserver.observe(obj, { childList: true });
                return mutationObserver;
            } else if (window.addEventListener) {
                obj.addEventListener('DOMNodeInserted', callback, false);
            }
        };
    })();

    const aList = document.getElementById('a_list');

    observeDOM(aList, mutations => {
        mutations.forEach(record => {
            record.addedNodes.forEach(node => {
                if (node.dataset?.answerType) {
                    const parent = document.getElementById(`arsu_va_${node.dataset.answerType}AnswersContainer`);
                    if (parent) {
                        parent.prepend(node);
                    }

                    const tab = document.getElementById(`arsu_va_tab-${node.dataset.answerType}`);
                    if (tab) {
                        tab.checked = true;
                    }
                }
            });
        });
    });
});