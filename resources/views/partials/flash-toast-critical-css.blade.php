{{-- До переноса JS в #flash-toast-stack не показывать в потоке (иначе мигание слева сверху). Дублирует app.css для первого кадра. --}}
<style>
    [data-auto-dismiss] { display: none !important; }
    #flash-toast-stack > .flash-toast.alert { display: grid !important; }
    #flash-toast-stack > .flash-toast.flash-toast--plain { display: block !important; }
</style>
<noscript>
    <style>
        [data-auto-dismiss] { display: block !important; }
        [data-auto-dismiss].alert { display: grid !important; }
    </style>
</noscript>
