<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines (Spanish)
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Debes aceptar :attribute para poder continuar.',
    'accepted_if' => 'El campo :attribute debe ser aceptado cuando :other es :value.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo debe contener letras.',
    'alpha_dash' => 'El campo :attribute solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo debe contener letras y números.',
    'array' => 'El campo :attribute debe ser un conjunto de elementos.',
    'ascii' => 'El campo :attribute solo debe contener caracteres alfanuméricos y símbolos de un solo byte.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El archivo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'can' => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor requerido.',
    'current_password' => 'La contraseña actual es incorrecta.',
    'date' => 'El campo :attribute no corresponde con una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no corresponde con el formato de fecha :format.',
    'decimal' => 'El campo :attribute debe tener :decimal lugares decimales.',
    'declined' => 'El campo :attribute debe ser rechazado.',
    'declined_if' => 'El campo :attribute debe ser rechazado cuando :other es :value.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'La imagen :attribute tiene dimensiones no válidas.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'doesnt_end_with' => 'El campo :attribute no debe terminar con uno de los siguientes valores: :values.',
    'doesnt_start_with' => 'El campo :attribute no debe comenzar con uno de los siguientes valores: :values.',
    'email' => 'Por favor, ingresa un correo electrónico válido.',
    'ends_with' => 'El campo :attribute debe terminar con uno de los siguientes valores: :values.',
    'enum' => 'El valor seleccionado para :attribute es inválido.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'extensions' => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute debe tener un valor.',
    'gt' => [
        'array' => 'El campo :attribute debe tener más de :value elementos.',
        'file' => 'El archivo :attribute debe pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute debe tener :value elementos o más.',
        'file' => 'El archivo :attribute debe pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o más.',
    ],
    'hex_color' => 'El campo :attribute debe ser un color hexadecimal válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El campo :attribute es inválido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena de texto JSON válida.',
    'list' => 'El campo :attribute debe ser una lista.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
        'file' => 'El archivo :attribute debe pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no debe tener más de :value elementos.',
        'file' => 'El archivo :attribute no debe pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
        'string' => 'El campo :attribute no debe tener más de :value caracteres.',
    ],
    'mac_address' => 'El campo :attribute debe ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El archivo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe contener más de :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no debe tener más de :max dígitos.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe contener al menos :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute debe tener al menos :min dígitos.',
    'missing' => 'El campo :attribute no debe estar presente.',
    'missing_if' => 'El campo :attribute no debe estar presente cuando :other es :value.',
    'missing_unless' => 'El campo :attribute no debe estar presente a menos que :other sea :value.',
    'missing_with' => 'El campo :attribute no debe estar presente cuando :values está presente.',
    'missing_with_all' => 'El campo :attribute no debe estar presente cuando :values están presentes.',
    'multiple_of' => 'El campo :attribute debe ser un múltiplo de :value.',
    'not_in' => 'El campo :attribute seleccionado no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'mixed' => 'La :attribute debe contener al menos una letra mayúscula y una minúscula.',
        'numbers' => 'La :attribute debe contener al menos un número.',
        'symbols' => 'La :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'La :attribute proporcionada ha aparecido en una filtración de datos. Por favor elige una diferente.',
    ],
    'present' => 'El campo :attribute debe estar presente.',
    'present_if' => 'El campo :attribute debe estar presente cuando :other es :value.',
    'present_unless' => 'El campo :attribute debe estar presente a menos que :other sea :value.',
    'present_with' => 'El campo :attribute debe estar presente cuando :values está presente.',
    'present_with_all' => 'El campo :attribute debe estar presente cuando :values están presentes.',
    'prohibited' => 'El campo :attribute está prohibido.',
    'prohibited_if' => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibited_unless' => 'El campo :attribute está prohibido a menos que :other esté en :values.',
    'prohibits' => 'El campo :attribute prohíbe que :other esté presente.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute debe contener entradas para: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando :other ha sido aceptado.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando :other ha sido rechazado.',
    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'size' => [
        'array' => 'El campo :attribute debe contener :size elementos.',
        'file' => 'El archivo :attribute debe pesar :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe contener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes valores: :values.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'timezone' => 'El campo :attribute debe ser una zona horaria válida.',
    'unique' => 'Este :attribute ya se encuentra registrado o en uso.',
    'uploaded' => 'No se pudo subir el archivo :attribute.',
    'uppercase' => 'El campo :attribute debe estar en mayúsculas.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'ulid' => 'El campo :attribute debe ser un ULID válido.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'name' => [
            'required' => 'Por favor, ingresa tu nombre completo.',
            'max' => 'El nombre no puede superar los 255 caracteres.',
        ],
        'email' => [
            'required' => 'El correo electrónico es obligatorio.',
            'email' => 'Por favor, ingresa un correo electrónico con formato válido.',
            'unique' => 'Este correo electrónico ya está registrado en Epycus.',
            'max' => 'El correo electrónico es demasiado largo.',
        ],
        'alias' => [
            'required' => 'El alias público es obligatorio para el ranking y tu perfil.',
            'unique' => 'Este alias ya está en uso. Prueba generar uno nuevo con el botón.',
            'max' => 'El alias no debe exceder los 40 caracteres.',
        ],
        'password' => [
            'required' => 'Debes ingresar una contraseña.',
            'confirmed' => 'Las contraseñas no coinciden.',
            'min' => 'La contraseña debe tener al menos :min caracteres.',
        ],
        'password_confirmation' => [
            'required' => 'Por favor, confirma tu contraseña.',
            'same' => 'Las contraseñas no coinciden.',
        ],
        'terms_accepted' => [
            'accepted' => 'Debes aceptar los Términos y Condiciones para crear tu cuenta.',
            'required' => 'Debes aceptar los Términos y Condiciones para crear tu cuenta.',
        ],
        'career' => [
            'required' => 'Por favor selecciona tu carrera profesional.',
            'in' => 'La carrera seleccionada no es válida.',
        ],
        'cycle' => [
            'required' => 'Selecciona tu ciclo académico actual.',
            'between' => 'El ciclo académico debe estar entre :min y :max.',
            'integer' => 'El ciclo académico debe ser un número válido.',
            'min' => 'El ciclo debe estar entre :min y :max.',
            'max' => 'El ciclo debe estar entre :min y :max.',
        ],
        'institution_type' => [
            'required' => 'Indica si estudias en Universidad o Instituto.',
            'in' => 'El tipo de institución debe ser Universidad o Instituto.',
        ],
        'avatar_gender' => [
            'required' => 'Selecciona el género de tu personaje.',
            'in' => 'El género seleccionado no es válido.',
        ],
        'avatar_style' => [
            'required' => 'El estilo de avatar es obligatorio.',
            'in' => 'El estilo de avatar seleccionado no es válido.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nombre completo',
        'email' => 'correo electrónico',
        'alias' => 'alias público',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'terms_accepted' => 'términos y condiciones',
        'career' => 'carrera profesional',
        'cycle' => 'ciclo académico',
        'institution_type' => 'tipo de institución',
        'avatar_gender' => 'género del avatar',
        'avatar_style' => 'estilo de avatar',
        'starts_at' => 'fecha de inicio',
        'ends_at' => 'fecha de fin',
        'start_time' => 'hora de inicio',
        'end_time' => 'hora de fin',
        'day_of_week' => 'día de la semana',
        'classroom' => 'aula',
        'sessions' => 'sesiones de clase',
        'credits' => 'créditos académicos',
        'target_grade' => 'meta de nota',
        'min_pass_grade' => 'nota mínima aprobatoria',
        'professor' => 'docente / profesor',
        'title' => 'título',
        'description' => 'descripción',
        'difficulty' => 'dificultad',
        'due_date' => 'fecha límite',
        'priority' => 'prioridad',
        'status' => 'estado',
        'amount' => 'monto',
        'category' => 'categoría',
        'question' => 'pregunta',
        'answer' => 'respuesta',
        'syllabus' => 'archivo de sílabo',
        'event_date' => 'fecha del evento',
    ],

];
