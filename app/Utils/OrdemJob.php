<?php

namespace App\Utils;

use App\Models\DemandaOrdemJob;

class OrdemJob
{
    public static function OrdemJobHelper($idUser, $demandaId) {
        DemandaOrdemJob::updateOrCreate([
            'usuario_id' => $idUser,
            'demanda_id' => $demandaId,
        ], [
            'usuario_id' => $idUser,
            'demanda_id' => $demandaId,
            'ordem' => 0
        ]);

        $jobs = DemandaOrdemJob::where('usuario_id', $idUser)
        ->where('demanda_id', '!=', $demandaId)
        ->from('demandas_ordem_jobs')
        ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC,  demandas_ordem_jobs.demanda_id  DESC')
        ->get();

        foreach ($jobs as $index => $job) {
            $job->update(['ordem' => $index + 1]);
            $job->save();
        }
    }
}
