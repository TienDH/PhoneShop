<div class="modal fade" id="product-variants-modal" tabindex="-1" aria-labelledby="variants-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="overflow-hidden">
                    <h2 class="modal-title h5 mb-1" id="variants-modal-title">Biến thể sản phẩm</h2>
                    <div class="text-muted small text-break" id="variants-product-name"></div>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="variants-message" class="alert" role="status" aria-live="polite" hidden></div>
                <div id="variant-delete-confirm" class="alert alert-danger" role="alert" hidden>
                    <p class="mb-2">Xóa variant <strong id="variant-delete-name" class="text-break"></strong>?</p>
                    <button type="button" class="btn btn-sm btn-danger" id="variant-delete-submit">
                        <i class="bi bi-trash me-1" aria-hidden="true"></i>Xóa variant
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="variant-delete-cancel">Hủy</button>
                </div>
                <div class="variants-toolbar d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
                    <h3 class="h6 mb-0">Danh sách variant <span class="badge bg-light text-dark border ms-1" id="variants-total">0</span></h3>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="variants-retry" hidden>
                            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Thử lại
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="variant-add">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm variant
                        </button>
                    </div>
                </div>
                <div class="variants-workspace" id="variants-workspace">
                    <section class="variants-list" aria-label="Danh sách variant">
                        <div class="table-responsive variants-table-wrap">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Ảnh</th>
                                        <th>Dung lượng</th>
                                        <th>Màu sắc / SKU</th>
                                        <th class="text-end">Giá</th>
                                        <th class="text-end">Tồn kho</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="variants-table-body"></tbody>
                            </table>
                        </div>
                    </section>
                    <section class="variant-editor" id="variant-editor" aria-labelledby="variant-form-title" hidden>
                        <h3 class="h6 mb-3" id="variant-form-title">Thêm variant</h3>
                        <form id="variant-form" enctype="multipart/form-data">
                            <fieldset id="variant-fields">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label for="variant-storage" class="form-label">Dung lượng</label>
                                        <input type="text" id="variant-storage" name="storage" class="form-control" maxlength="100" required aria-describedby="variant-storage-error">
                                        <div class="invalid-feedback" id="variant-storage-error" data-error-for="storage"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="variant-color" class="form-label">Màu sắc</label>
                                        <input type="text" id="variant-color" name="color" class="form-control" maxlength="100" required aria-describedby="variant-color-error">
                                        <div class="invalid-feedback" id="variant-color-error" data-error-for="color"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="variant-price" class="form-label">Giá (VNĐ)</label>
                                        <input type="number" id="variant-price" name="price" class="form-control" min="0" max="9999999999999.99" step="0.01" required aria-describedby="variant-price-error">
                                        <div class="invalid-feedback" id="variant-price-error" data-error-for="price"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="variant-stock" class="form-label">Tồn kho</label>
                                        <input type="number" id="variant-stock" name="stock" class="form-control" min="0" max="4294967295" step="1" required aria-describedby="variant-stock-error">
                                        <div class="invalid-feedback" id="variant-stock-error" data-error-for="stock"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="variant-sku" class="form-label">SKU</label>
                                        <input type="text" id="variant-sku" name="sku" class="form-control" maxlength="255" required aria-describedby="variant-sku-error">
                                        <div class="invalid-feedback" id="variant-sku-error" data-error-for="sku"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="variant-image" class="form-label">Ảnh</label>
                                        <input type="file" id="variant-image" name="image" class="form-control" accept="image/*" aria-describedby="variant-image-error">
                                        <div class="invalid-feedback" id="variant-image-error" data-error-for="image"></div>
                                        <img id="variant-image-preview" class="variant-image-preview mt-2" alt="Ảnh variant" hidden>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-3 flex-wrap">
                                    <button type="submit" class="btn btn-primary" id="variant-save">
                                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i><span>Lưu variant</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="variant-form-cancel">Hủy</button>
                                </div>
                            </fieldset>
                        </form>
                    </section>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small text-break" id="variants-footer-name"></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<template id="variant-row-template">
    <tr>
        <td>
            <img class="table-image" data-variant-image hidden>
            <span class="variant-image-empty" data-variant-no-image><i class="bi bi-image" aria-hidden="true"></i></span>
        </td>
        <td class="fw-bold" data-variant-storage></td>
        <td class="variant-identity">
            <div data-variant-color></div>
            <div class="text-muted small text-break" data-variant-sku></div>
        </td>
        <td class="text-end text-nowrap" data-variant-price></td>
        <td class="text-end"><span class="variant-stock-label">Tồn kho </span><span class="badge" data-variant-stock></span></td>
        <td class="text-end text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-variant-edit title="Chỉnh sửa variant" aria-label="Chỉnh sửa variant">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-variant-delete title="Xóa variant" aria-label="Xóa variant">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
</template>

@push('styles')
    <link href="{{ asset('css/admin-product-variants.css') }}" rel="stylesheet">
@endpush
@push('scripts')
    <script src="{{ asset('js/admin-product-variants.js') }}" defer></script>
@endpush
