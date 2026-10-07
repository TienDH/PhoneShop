document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('product-variants-modal');
    if (!modal) return;

    const body = document.getElementById('variants-table-body');
    const template = document.getElementById('variant-row-template');
    const message = document.getElementById('variants-message');
    const editor = document.getElementById('variant-editor');
    const workspace = document.getElementById('variants-workspace');
    const form = document.getElementById('variant-form');
    const preview = document.getElementById('variant-image-preview');
    const addButton = document.getElementById('variant-add');
    const retryButton = document.getElementById('variants-retry');
    const deleteConfirm = document.getElementById('variant-delete-confirm');
    const saveLabel = document.querySelector('#variant-save span');
    const money = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 2 });
    let trigger = null;
    let variants = [];
    let editingVariant = null;
    let deletingVariant = null;
    let busy = false;
    let loaded = false;
    let controller = null;
    let previewUrl = null;

    function showMessage(text, type = 'danger') {
        message.textContent = text;
        message.className = `alert alert-${type}`;
        message.hidden = false;
    }

    function clearErrors() {
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        });
        form.querySelectorAll('[data-error-for]').forEach(element => element.textContent = '');
    }

    function showErrors(error) {
        showMessage(error.errors ? 'Vui lòng kiểm tra thông tin variant.' : error.message);
        Object.entries(error.errors || {}).forEach(([field, errors]) => {
            const input = form.elements.namedItem(field);
            const feedback = form.querySelector(`[data-error-for="${field}"]`);
            if (input && feedback) {
                input.classList.add('is-invalid');
                input.setAttribute('aria-invalid', 'true');
                feedback.textContent = errors[0];
            }
        });
        form.querySelector('.is-invalid')?.focus();
    }

    function setBusy(value) {
        busy = value;
        modal.setAttribute('aria-busy', String(value));
        modal.querySelectorAll('button').forEach(button => button.disabled = value);
        document.getElementById('variant-fields').disabled = value;
        addButton.disabled = value || !loaded;
        saveLabel.textContent = value ? 'Đang lưu...' : 'Lưu variant';
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        if (response.redirected || response.status === 401 || response.status === 419) {
            throw new Error('Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang và đăng nhập.');
        }
        if (!response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('Không thể tải dữ liệu. Vui lòng thử lại.');
        }
        const data = await response.json();
        if (!response.ok) {
            const text = response.status === 404
                ? 'Sản phẩm hoặc variant không còn tồn tại. Vui lòng tải lại danh sách.'
                : 'Không thể lưu thay đổi. Vui lòng thử lại.';
            throw Object.assign(new Error(text), { errors: data.errors });
        }
        return data;
    }

    function updateCount(count) {
        document.getElementById('variants-total').textContent = count;
        trigger.querySelector('[data-variant-count]').textContent = count;
    }

    function renderState(text, loading = false) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 6;
        cell.className = 'text-center text-muted py-4';
        if (loading) {
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm me-2';
            spinner.setAttribute('aria-hidden', 'true');
            cell.append(spinner);
        }
        cell.append(document.createTextNode(text));
        row.append(cell);
        body.replaceChildren(row);
    }

    function render() {
        body.replaceChildren();
        if (!variants.length) {
            renderState('Sản phẩm chưa có variant.');
            return;
        }
        variants.forEach(variant => {
            const row = template.content.firstElementChild.cloneNode(true);
            row.dataset.variantId = variant.id;
            row.classList.toggle('is-selected', editingVariant?.id === variant.id);
            row.querySelector('[data-variant-storage]').textContent = variant.storage;
            row.querySelector('[data-variant-color]').textContent = variant.color;
            row.querySelector('[data-variant-sku]').textContent = variant.sku;
            row.querySelector('[data-variant-price]').textContent = money.format(Number(variant.price));
            const stock = row.querySelector('[data-variant-stock]');
            stock.textContent = variant.stock;
            stock.classList.add(Number(variant.stock) > 0 ? 'bg-success' : 'bg-secondary');
            const image = row.querySelector('[data-variant-image]');
            if (variant.image_url) {
                image.src = variant.image_url;
                image.alt = variant.sku;
                image.hidden = false;
                row.querySelector('[data-variant-no-image]').hidden = true;
            }
            row.querySelector('[data-variant-edit]').addEventListener('click', () => openEditor(variant));
            row.querySelector('[data-variant-delete]').addEventListener('click', () => {
                deletingVariant = variant;
                document.getElementById('variant-delete-name').textContent = `${variant.storage} / ${variant.color} (${variant.sku})`;
                deleteConfirm.hidden = false;
                message.hidden = true;
                deleteConfirm.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                document.getElementById('variant-delete-cancel').focus();
            });
            body.append(row);
        });
    }

    function showPreview(url) {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        preview.hidden = !url;
        if (url) preview.src = url;
        else preview.removeAttribute('src');
    }

    function closeEditor() {
        editingVariant = null;
        editor.hidden = true;
        workspace.classList.remove('is-editing');
        form.reset();
        clearErrors();
        showPreview(null);
        body.querySelectorAll('.is-selected').forEach(row => row.classList.remove('is-selected'));
    }

    function openEditor(variant = null) {
        form.reset();
        clearErrors();
        editingVariant = variant;
        deletingVariant = null;
        deleteConfirm.hidden = true;
        message.hidden = true;
        if (variant) {
            ['storage', 'color', 'price', 'stock', 'sku'].forEach(field => form.elements.namedItem(field).value = variant[field]);
        }
        document.getElementById('variant-form-title').textContent = variant ? 'Chỉnh sửa variant' : 'Thêm variant';
        showPreview(variant?.image_url);
        editor.hidden = false;
        workspace.classList.add('is-editing');
        render();
        form.elements.storage.focus();
    }

    async function loadVariants() {
        controller?.abort();
        controller = new AbortController();
        loaded = false;
        addButton.disabled = true;
        retryButton.hidden = true;
        message.hidden = true;
        renderState('Đang tải variant...', true);
        try {
            const data = await request(trigger.dataset.variantsUrl, { signal: controller.signal });
            variants = data.variants;
            loaded = true;
            updateCount(data.count);
            render();
        } catch (error) {
            if (error.name === 'AbortError') return;
            renderState('Không thể tải danh sách variant.');
            showMessage(error.message);
            retryButton.hidden = false;
        } finally {
            addButton.disabled = !loaded;
        }
    }

    modal.addEventListener('show.bs.modal', event => {
        trigger = event.relatedTarget;
        if (!trigger) {
            event.preventDefault();
            return;
        }
        variants = [];
        closeEditor();
        deleteConfirm.hidden = true;
        deletingVariant = null;
        document.getElementById('variants-product-name').textContent = trigger.dataset.productName;
        document.getElementById('variants-footer-name').textContent = trigger.dataset.productName;
        document.getElementById('variants-total').textContent = trigger.querySelector('[data-variant-count]').textContent;
        loadVariants();
    });
    modal.addEventListener('hide.bs.modal', event => {
        if (busy) event.preventDefault();
        else controller?.abort();
    });
    modal.addEventListener('hidden.bs.modal', closeEditor);
    addButton.addEventListener('click', () => openEditor());
    retryButton.addEventListener('click', loadVariants);
    document.getElementById('variant-form-cancel').addEventListener('click', () => {
        closeEditor();
        message.hidden = true;
        addButton.focus();
    });
    document.getElementById('variant-delete-cancel').addEventListener('click', () => {
        deletingVariant = null;
        deleteConfirm.hidden = true;
    });
    form.elements.image.addEventListener('change', () => {
        const file = form.elements.image.files[0];
        showPreview(file ? null : editingVariant?.image_url);
        if (file) {
            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.hidden = false;
        }
    });
    form.addEventListener('input', event => {
        if (event.target.classList.contains('is-invalid')) {
            event.target.classList.remove('is-invalid');
            event.target.removeAttribute('aria-invalid');
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !loaded) return;
        const data = new FormData(form);
        if (editingVariant) data.append('_method', 'PUT');
        const url = editingVariant ? editingVariant.url : trigger.dataset.variantsUrl;
        clearErrors();
        message.hidden = true;
        setBusy(true);
        try {
            const result = await request(url, { method: 'POST', body: data });
            const index = variants.findIndex(variant => variant.id === result.variant.id);
            if (index === -1) variants.unshift(result.variant);
            else variants[index] = result.variant;
            updateCount(result.count);
            closeEditor();
            render();
            showMessage(result.message, 'success');
        } catch (error) {
            showErrors(error);
        } finally {
            setBusy(false);
            form.querySelector('.is-invalid')?.focus();
        }
    });

    document.getElementById('variant-delete-submit').addEventListener('click', async () => {
        if (busy || !deletingVariant) return;
        setBusy(true);
        try {
            const result = await request(deletingVariant.url, { method: 'DELETE' });
            variants = variants.filter(variant => variant.id !== deletingVariant.id);
            if (editingVariant?.id === deletingVariant.id) closeEditor();
            deletingVariant = null;
            deleteConfirm.hidden = true;
            updateCount(result.count);
            render();
            showMessage(result.message, 'success');
        } catch (error) {
            showMessage(error.message);
        } finally {
            setBusy(false);
        }
    });
});
