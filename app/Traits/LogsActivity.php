<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity($action, $description = null, $metadata = [])
    {
        $type = strtolower(class_basename($this)) . '_' . $action;
        $description = $description ?? $this->getActivityDescription($action);
        
        ActivityLog::create([
            'type' => $type,
            'description' => $description,
            'user_id' => auth()->id(),
            'metadata' => array_merge($metadata, [
                'model_id' => $this->id,
                'model_type' => get_class($this),
                'action' => $action
            ])
        ]);
    }

    protected function getActivityDescription($action)
    {
        $modelName = strtolower(class_basename($this));
        return ucfirst($modelName) . ' was ' . $action;
    }
} 