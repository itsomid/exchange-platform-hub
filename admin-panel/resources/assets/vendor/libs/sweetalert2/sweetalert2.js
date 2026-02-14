import Swal from 'sweetalert2';

try {
    window.Swal = Swal;
} catch (e) { }

export { Swal };
