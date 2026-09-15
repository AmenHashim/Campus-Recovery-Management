CPRMS — Campus Property Recovery & Management System
University Recovery System

{{ $heading }}
@isset($greeting)

{{ $greeting }}
@endisset
@foreach ((array) $lines as $line)

{{ $line }}
@endforeach
@isset($actionUrl)

{{ $actionText }}:
{{ $actionUrl }}
@endisset
@isset($outro)

{{ $outro }}
@endisset

--
This is an automated message from the University Recovery System Lost & Found Office — please don't reply to it.
© {{ date('Y') }} University Recovery System · CPRMS
