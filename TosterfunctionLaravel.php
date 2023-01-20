<script>
    function Toaster(message) {
        Command: toastr["success"](message)
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "hideDuration": "1000",
            "timeOut": "1000",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
    }

    //success massage strip disable
    $(function() {
        $(".alert").delay(3000).fadeOut("slow");
    });
</script>
use function in pages
//Toaster(response.success);