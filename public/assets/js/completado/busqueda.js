function buscarRecibos(page = 1) {
    var searchTerm = document.getElementById('searchInput').value;
    
    $.ajax({
        url: '/buscarCompleto?page=' + page,
        type: 'GET',
        data: { search: searchTerm },
        success: function(response) {
            $('#recibosBody').html(response.recibosBodyHtml);
            $('#paginacion').html(response.paginationLinks);
        },
        error: function(jqXHR, textStatus, errorThrown) {
           
        }
    });
}

// Capturar clicks en los enlaces de paginación y hacer AJAX en lugar de recargar página
$(document).on('click', '#paginacion a', function(e) {
    e.preventDefault();
    var page = $(this).attr('href').split('page=')[1];
    buscarRecibos(page);
});


//alerta model success y error
document.addEventListener('DOMContentLoaded', function() {
    var successAlertModal = document.getElementById('success-alert-modal');
    var errorAlertModal = document.getElementById('error-alert-modal');
    var errorAlert = document.getElementById('error-alert');

    var successProgressBar = document.getElementById('success-progress-bar');
    var errorProgressBarModal = document.getElementById('error-progress-bar');
    var errorProgressBarAlert = document.getElementById('error-progress-bar');

    if (successAlertModal && successProgressBar) {
        setTimeout(function () {
            successProgressBar.style.width = '100%';
        }, 10); // Retraso para permitir la renderización inicial

        setTimeout(function () {
            successAlertModal.classList.add('hidden');
            setTimeout(function () {
                successAlertModal.style.display = 'none';
            }, 500); // 0.5 segundos para la transición de opacidad
        }, 2000); // 2 segundos para que la barra se llene
    }

    if (errorAlertModal && errorProgressBarModal) {
        setTimeout(function () {
            errorProgressBarModal.style.width = '100%';
        }, 10); // Retraso para permitir la renderización inicial

        setTimeout(function () {
            errorAlertModal.classList.add('hidden');
            setTimeout(function () {
                errorAlertModal.style.display = 'none';
            }, 500); // 0.5 segundos para la transición de opacidad
        }, 2000); // 2 segundos para que la barra se llene
    }

    if (errorAlert && errorProgressBarAlert) {
        setTimeout(function () {
            errorProgressBarAlert.style.width = '100%';
        }, 10); // Retraso para permitir la renderización inicial

        setTimeout(function () {
            errorAlert.classList.add('hidden');
            setTimeout(function () {
                errorAlert.style.display = 'none';
            }, 500); // 0.5 segundos para la transición de opacidad
        }, 2000); // 2 segundos para que la barra se llene
    }
});
