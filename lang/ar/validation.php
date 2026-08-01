<?php

return [

    /*
    |--------------------------------------------------------------------
    | لغة رسائل التحقق (Validation Language Lines)
    |--------------------------------------------------------------------
    |
    | نفس مفاتيح القواعد الافتراضية في Laravel، مترجمة للعربية. تُستخدم
    | تلقائياً من قِبل Form Requests عندما تكون اللغة الحالية 'ar'
    | (يضبطها SetLocaleFromRequest middleware) — لا حاجة لأي تعديل كود.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url' => ':attribute ليس رابطاً صحيحاً.',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخاً بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute قائمة (array).',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفاً.',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة (true/false).',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'date_equals' => 'يجب أن يكون :attribute تاريخاً مساوياً لـ :date.',
    'date_format' => ':attribute لا يطابق الصيغة :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منزلة عشرية.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يتكوّن :attribute من :digits أرقام.',
    'digits_between' => 'يجب أن يتكوّن :attribute من عدد أرقام بين :min و :max.',
    'distinct' => ':attribute يحتوي على قيمة مكررة.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'exists' => ':attribute المُحدد غير موجود.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'filled' => 'حقل :attribute مطلوب.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفاً.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عنصر على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من أو يساوي :value حرفاً.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المُختارة لـ :attribute غير صحيحة.',
    'in_array' => 'حقل :attribute غير موجود ضمن :other.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيحاً.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيحاً.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيحاً.',
    'json' => 'يجب أن يكون :attribute نص JSON صحيحاً.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفاً.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من أو يساوي :value حرفاً.',
    ],
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يتجاوز حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز طول :attribute عن :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصر على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل طول :attribute عن :min حرفاً.',
    ],
    'not_in' => 'القيمة المُختارة لـ :attribute غير صحيحة.',
    'not_regex' => 'صيغة :attribute غير صحيحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'password' => 'كلمة المرور غير صحيحة.',
    'present' => 'حقل :attribute مطلوب.',
    'prohibited' => 'حقل :attribute محظور.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفاً.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => ':attribute مُستخدَم من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'صيغة :attribute غير صحيحة.',
    'uuid' => 'يجب أن يكون :attribute مُعرِّف UUID صحيحاً.',

    /*
    |--------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------
    | رسائل مخصّصة حسب مسمى الحقل + القاعدة، إن احتجتها لاحقاً:
    | 'patient.first_name.required' => 'الاسم الأول مطلوب.'
    */
    'custom' => [],

    /*
    |--------------------------------------------------------------------
    | أسماء الحقول بالعربية — تحل محل :attribute في الرسائل أعلاه
    |--------------------------------------------------------------------
    | يُنصَح بإكمال هذه القائمة تدريجياً مع كل Form Request جديد، حتى
    | لا تظهر أسماء الحقول بالإنجليزية داخل رسالة عربية.
    */
    'attributes' => [
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'gender' => 'الجنس',
        'birth_date' => 'تاريخ الميلاد',
        'national_id' => 'رقم الهوية',
        'address' => 'العنوان',
        'branch_id' => 'الفرع',
        'doctor_profile_id' => 'الطبيب',
        'patient_id' => 'المريض',
        'appointment_date' => 'تاريخ الموعد',
        'start_time' => 'وقت البداية',
        'end_time' => 'وقت النهاية',
        'reason' => 'سبب الزيارة',
        'service_id' => 'الخدمة',
        'quantity' => 'الكمية',
        'unit_price' => 'سعر الوحدة',
        'discount_amount' => 'قيمة الخصم',
        'tooth_number' => 'رقم السن',
        'amount' => 'المبلغ',
        'payment_method' => 'طريقة الدفع',
        'payment_date' => 'تاريخ الدفعة',
    ],

];
