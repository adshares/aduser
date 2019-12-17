import $ from 'jquery'
import 'bootstrap/js/dist/alert'
import 'bootstrap/js/dist/dropdown'
import 'bootstrap/js/dist/popover'
import '@fortawesome/fontawesome-free/js/fontawesome'
import '@fortawesome/fontawesome-free/js/regular'
import '@fortawesome/fontawesome-free/js/solid'
import '@fortawesome/fontawesome-free/css/fontawesome.css'
import '@fortawesome/fontawesome-free/css/regular.css'
import '@fortawesome/fontawesome-free/css/solid.css'
import './panel'

$(document).ready(function () {
    $('[data-toggle="popover"]').popover()
});
