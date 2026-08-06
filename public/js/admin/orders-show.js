(function () {
    const form = document.getElementById('order-edit-form');
    const shipForm = document.getElementById('order-ship-form');

    if (form) {
        const startButton = form.querySelector('.order-edit-form__start');
        const saveButton = form.querySelector('.order-edit-form__save');
        const cancelButton = form.querySelector('.order-edit-form__cancel');
        const viewBlocks = form.querySelectorAll('.order-edit-form__view');
        const fieldBlocks = form.querySelectorAll('.order-edit-form__fields');
        const itemsBody = document.getElementById('order-items-body');
        const itemTemplate = document.getElementById('order-item-row-template');
        const addItemButton = form.querySelector('.order-items-add');
        const initialValues = new Map();
        let nextItemIndex = itemsBody ? itemsBody.querySelectorAll('.order-item-row').length : 0;

        const fieldInputs = () => form.querySelectorAll('input, textarea, select');

        const captureValues = () => {
            initialValues.clear();
            fieldInputs().forEach((input) => {
                if (!input.name) {
                    return;
                }

                if (input.type === 'checkbox') {
                    initialValues.set(input.name, input.checked);
                } else {
                    initialValues.set(input.name, input.value);
                }
            });
        };

        captureValues();

        const setEditing = (editing) => {
            form.classList.toggle('is-editing', editing);
            startButton.hidden = editing;
            saveButton.hidden = !editing;
            cancelButton.hidden = !editing;
            viewBlocks.forEach((block) => {
                block.hidden = editing;
            });
            fieldBlocks.forEach((block) => {
                block.hidden = !editing;
            });
        };

        if (form.classList.contains('is-editing')) {
            setEditing(true);
        } else {
            setEditing(false);
        }

        startButton.addEventListener('click', () => {
            setEditing(true);
        });

        cancelButton.addEventListener('click', () => {
            fieldInputs().forEach((input) => {
                if (!input.name) {
                    return;
                }

                if (input.type === 'checkbox') {
                    input.checked = initialValues.get(input.name) ?? false;
                } else {
                    input.value = initialValues.get(input.name) ?? '';
                }
            });

            if (itemsBody && itemTemplate) {
                itemsBody.querySelectorAll('.order-item-row--new').forEach((row) => row.remove());
                nextItemIndex = itemsBody.querySelectorAll('.order-item-row').length;
            }

            setEditing(false);
        });

        if (addItemButton && itemsBody && itemTemplate) {
            addItemButton.addEventListener('click', () => {
                const index = `new_${nextItemIndex++}`;
                const html = itemTemplate.innerHTML.replaceAll('__INDEX__', index);
                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = html.trim();
                const row = wrapper.firstElementChild;
                itemsBody.appendChild(row);
            });
        }

        form.addEventListener('submit', (event) => {
            if (!window.confirm('変更を保存しますか？')) {
                event.preventDefault();
                return;
            }

            captureValues();
        });
    }

    if (!shipForm) {
        return;
    }

    const shippingTypeInputs = shipForm.querySelectorAll('[name="shipping_type"]');
    const shippingMailFields = document.getElementById('shipping-mail-fields');
    const sendShippingMail = document.getElementById('send_shipping_mail');
    const shippingMailEditor = document.getElementById('shipping-mail-editor');
    const shippingMailSubject = document.getElementById('shipping_mail_subject');
    const shippingMailBody = document.getElementById('shipping_mail_body');
    const trackingNumberInput = shipForm.querySelector('[name="tracking_number"]');
    let appliedShippingAction = null;
    let mailEditorDirty = false;

    const shippingTemplates = (() => {
        if (!shippingMailFields?.dataset.templates) {
            return null;
        }

        try {
            return JSON.parse(shippingMailFields.dataset.templates);
        } catch (error) {
            return null;
        }
    })();

    const resolveTrackingLine = (templateBody) => {
        const tracking = trackingNumberInput?.value.trim() ?? '';
        const trackingLine = tracking ? `追跡番号: ${tracking}` : '';
        return templateBody
            .replaceAll('{{TRACKING_LINE}}', trackingLine)
            .replace(/\n{3,}/g, '\n\n')
            .trim() + '\n';
    };

    const selectedShippingAction = () => {
        const checked = shipForm.querySelector('[name="shipping_type"]:checked');
        if (checked) {
            return checked.value === 'partial' ? 'partial' : 'full';
        }

        const hidden = shipForm.querySelector('[name="shipping_type"][type="hidden"]');
        if (hidden) {
            return hidden.value === 'partial' ? 'partial' : 'full';
        }

        return 'full';
    };

    const applyShippingTemplate = (action, force = false) => {
        if (!shippingTemplates || !shippingMailSubject || !shippingMailBody) {
            return;
        }

        const template = action === 'partial' ? shippingTemplates.partial : shippingTemplates.full;
        if (!template) {
            return;
        }

        if (!force && mailEditorDirty && appliedShippingAction === action) {
            return;
        }

        shippingMailSubject.value = template.subject;
        shippingMailBody.value = resolveTrackingLine(template.body);
        appliedShippingAction = action;
        mailEditorDirty = false;
    };

    const syncShippingMailFields = () => {
        if (!shippingMailFields) {
            return;
        }

        const action = selectedShippingAction();

        if (sendShippingMail && !sendShippingMail.dataset.userToggled) {
            sendShippingMail.checked = true;
        }

        if (shippingMailEditor) {
            shippingMailEditor.hidden = !(sendShippingMail?.checked ?? true);
        }

        if (appliedShippingAction !== action) {
            applyShippingTemplate(action, true);
        } else if (!mailEditorDirty) {
            applyShippingTemplate(action, false);
        }
    };

    shippingTypeInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (sendShippingMail) {
                delete sendShippingMail.dataset.userToggled;
            }

            syncShippingMailFields();
        });
    });

    sendShippingMail?.addEventListener('change', () => {
        sendShippingMail.dataset.userToggled = '1';
        if (shippingMailEditor) {
            shippingMailEditor.hidden = !sendShippingMail.checked;
        }
    });

    shippingMailSubject?.addEventListener('input', () => {
        mailEditorDirty = true;
    });

    shippingMailBody?.addEventListener('input', () => {
        mailEditorDirty = true;
    });

    trackingNumberInput?.addEventListener('input', () => {
        const action = selectedShippingAction();
        if (action && !mailEditorDirty) {
            applyShippingTemplate(action, true);
        }
    });

    syncShippingMailFields();

    shipForm.addEventListener('submit', (event) => {
        const customizedInput = shipForm.querySelector('[name="shipping_mail_customized"]');
        if (customizedInput) {
            customizedInput.value = mailEditorDirty ? '1' : '0';
        }

        if (!mailEditorDirty) {
            if (shippingMailSubject) {
                shippingMailSubject.disabled = true;
            }
            if (shippingMailBody) {
                shippingMailBody.disabled = true;
            }
        }

        const tracking = trackingNumberInput?.value.trim() ?? '';
        if (!tracking) {
            event.preventDefault();
            window.alert('発送処理には追跡番号が必要です。');
            trackingNumberInput?.focus();
            return;
        }

        const action = selectedShippingAction();
        const willSendMail = Boolean(sendShippingMail?.checked);

        let message = '発送処理を行いますか？';
        if (action === 'partial') {
            message = willSendMail
                ? '一部発送にし、メールを送信しますか？'
                : '一部発送にしますか？（メールは送りません）';
        } else {
            message = willSendMail
                ? '発送済みにし、メールを送信しますか？'
                : '発送済みにしますか？（メールは送りません）';
        }

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
