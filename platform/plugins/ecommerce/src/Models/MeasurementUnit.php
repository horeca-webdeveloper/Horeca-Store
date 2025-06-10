<?php
// app/Models/ProductAttribute.php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class MeasurementUnit extends BaseModel
{
	public function type()
	{
		return $this->belongsTo(MeasurementType::class, 'measurement_type_id');
	}

	public function measurementUnitAttributes()
	{
		return $this->belongsToMany(Attribute::class, 'attribute_measurements')->using(AttributeMeasurement::class);
	}
}
