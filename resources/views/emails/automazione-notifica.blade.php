@component('mail::message')

{!! nl2br(e($compiledBody)) !!}

@if(!empty($documentLinks))

---

**Documenti allegati**

@foreach($documentLinks as $doc)
@component('mail::button', ['url' => $doc['url'], 'color' => 'primary'])
{{ $doc['nome_file'] }}
@endcomponent

@endforeach
@endif

---
*Messaggio inviato automaticamente da **{{ $tenantName }}**.*

@endcomponent
