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
        const cancelReason = form.querySelector('[name="cancel_reason"]');
        const refundAmount = form.querySelector('[name="refund_amount"]');
        const markAsPaid = form.querySelector('[name="mark_as_paid"]');
        const markAsShipped = form.querySelector('[name="mark_as_shipped"]');

        let message = '変更を保存しますか？';

        if (cancelReason?.value.trim()) {
            message = '注文をキャンセルしますか？';
        } else if (refundAmount?.value) {
            message = '返金を記録しますか？';
        } else if (markAsPaid?.checked && markAsShipped?.checked) {
            message = '入金確認と発送処理を行いますか？';
        } else if (markAsPaid?.checked) {
            message = '入金確認しますか？';
        } else if (markAsShipped?.checked) {
            message = '発送済みにしますか？';
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        captureValues();
    });
})();
