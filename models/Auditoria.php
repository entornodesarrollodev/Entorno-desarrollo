<?php
declare(strict_types=1);
final class Auditoria
{
    // Este bloque conserva los cambios de entidades en la bitácora de auditoría.
    public function __construct(private PDO $conexion) {}
    // Este bloque registra la acción y sus estados anterior y posterior.
    public function registrar(?int $usuarioId,string $entidad,int $entidadId,string $accion,?array $antes,array $despues):void{$q=$this->conexion->prepare('INSERT INTO auditoria_cambios (usuario_id,entidad,entidad_id,accion,datos_anteriores,datos_nuevos) VALUES (:usuario,:entidad,:id,:accion,:antes,:despues)');$q->execute(['usuario'=>$usuarioId,'entidad'=>$entidad,'id'=>$entidadId,'accion'=>$accion,'antes'=>$antes===null?null:json_encode($antes,JSON_UNESCAPED_UNICODE),'despues'=>json_encode($despues,JSON_UNESCAPED_UNICODE)]);}
}
