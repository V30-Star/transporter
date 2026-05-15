<style>
    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .2);
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0
    }

    .slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #ccc;
        transition: .4s;
        border-radius: 34px
    }

    .slider:before {
        content: "";
        position: absolute;
        height: 26px;
        width: 26px;
        border-radius: 50%;
        left: 4px;
        bottom: 4px;
        background: #fff;
        transition: .4s
    }

    input:checked+.slider {
        background: #4CAF50
    }

    input:checked+.slider:before {
        transform: translateX(26px)
    }

    [x-cloak] {
        display: none !important
    }

    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
