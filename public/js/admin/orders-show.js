(function () {
    const form = document.getElementById('order-edit-form');
    if (!form) {
        return;
    }

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
        syncShippingMailFields();
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

    const markAsShipped = form.querySelector('[name="mark_as_shipped"]');
    const markAsPartiallyShipped = form.querySelector('[name="mark_as_partially_shipped"]');
    const shippingMailFields = document.getElementById('shipping-mail-fields');
    const sendShippingMail = document.getElementById('send_shipping_mail');
    const shippingMailEditor = document.getElementById('shipping-mail-editor');
    const shippingMailSubject = document.getElementById('shipping_mail_subject');
    const shippingMailBody = document.getElementById('shipping_mail_body');
    const trackingNumberInput = form.querySelector('[name="tracking_number"]');
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
        if (markAsPartiallyShipped?.checked) {
            return 'partial';
        }

        if (markAsShipped?.checked) {
            return 'full';
        }

        return null;
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
        shippingMailFields.hidden = !action;

        if (!action) {
            appliedShippingAction = null;
            return;
        }

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

    [markAsShipped, markAsPartiallyShipped].forEach((checkbox) => {
        checkbox?.addEventListener('change', () => {
            if (!checkbox.checked) {
                syncShippingMailFields();
                return;
            }

            const other = checkbox === markAsShipped ? markAsPartiallyShipped : markAsShipped;
            if (other?.checked) {
                other.checked = false;
            }

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

    form.addEventListener('submit', (event) => {
        const cancelReason = form.querySelector('[name="cancel_reason"]');
        const refundAmount = form.querySelector('[name="refund_amount"]');
        const markAsPaid = form.querySelector('[name="mark_as_paid"]');
        const revertShippingStatus = form.querySelector('[name="revert_shipping_status"]');
        const willSendMail = Boolean(selectedShippingAction() && sendShippingMail?.checked);

        let message = '変更を保存しますか？';

        if (cancelReason?.value.trim()) {
            message = '注文をキャンセルしますか？';
        } else if (refundAmount?.value) {
            message = '返金を記録しますか？';
        } else if (revertShippingStatus?.value) {
            const label = revertShippingStatus.selectedOptions[0]?.textContent?.trim() ?? '選択した状態';
            message = `${label}に変更しますか？（メールは送りません）`;
        } else if (markAsPaid?.checked && markAsShipped?.checked) {
            message = willSendMail
                ? '入金確認と発送処理を行い、メールを送信しますか？'
                : '入金確認と発送処理を行いますか？';
        } else if (markAsPaid?.checked && markAsPartiallyShipped?.checked) {
            message = willSendMail
                ? '入金確認と一部発送を行い、メールを送信しますか？'
                : '入金確認と一部発送を行いますか？';
        } else if (markAsPaid?.checked) {
            message = '入金確認しますか？';
        } else if (markAsShipped?.checked) {
            message = willSendMail
                ? '発送済みにし、メールを送信しますか？'
                : '発送済みにしますか？（メールは送りません）';
        } else if (markAsPartiallyShipped?.checked) {
            message = willSendMail
                ? '一部発送にし、メールを送信しますか？'
                : '一部発送にしますか？（メールは送りません）';
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        captureValues();
    });
})();
