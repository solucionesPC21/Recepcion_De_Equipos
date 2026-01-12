<!-- _form_registro_cliente.blade.php -->
<div class="modal fade" id="registerClientModal" tabindex="-1" aria-labelledby="registerClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerClientModalLabel">Registrar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm">
                    <div class="mb-3">
                        <label for="clientNameInput" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="clientNameInput" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientPhoneInput" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="clientPhoneInput">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveClient">Guardar</button>
            </div>
        </div>
    </div>
</div>