document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('orders-management-form');
    const selectAll = document.getElementById('select-all-orders');
    const selectedCount = document.getElementById('selected-count');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkActionSubmit = document.getElementById('bulk-action-submit');

    if (!form || !selectAll || !selectedCount) {
        return;
    }

    const orderCheckboxes = () => Array.from(form.querySelectorAll('.order-select-checkbox'));

    const updateSelectedCount = () => {
        const checkedCount = orderCheckboxes().filter((checkbox) => checkbox.checked).length;
        selectedCount.textContent = `${checkedCount}件選択`;
        selectAll.checked = checkedCount > 0 && checkedCount === orderCheckboxes().length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < orderCheckboxes().length;
    };

    selectAll.addEventListener('change', () => {
        orderCheckboxes().forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSelectedCount();
    });

    orderCheckboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    form.addEventListener('submit', (event) => {
        const submitter = event.submitter;

        if (submitter?.getAttribute('formaction')) {
            form.target = '';
            return;
        }

        const checkedCount = orderCheckboxes().filter((checkbox) => checkbox.checked).length;

        if (checkedCount === 0) {
            event.preventDefault();
            window.alert('注文を1件以上選択してください。');
            return;
        }

        const actionLabel = bulkActionSelect?.selectedOptions[0]?.textContent ?? '一括操作';

        if (!window.confirm(`選択した ${checkedCount} 件に「${actionLabel}」を実行しますか？`)) {
            event.preventDefault();
            return;
        }

        if (bulkActionSelect?.value === 'print_receipt') {
            form.target = '_blank';
        } else {
            form.target = '';
        }
    });

    if (bulkActionSubmit) {
        bulkActionSubmit.addEventListener('click', () => {
            form.target = '';
        });
    }

    updateSelectedCount();
});
