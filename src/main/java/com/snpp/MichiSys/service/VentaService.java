package com.snpp.MichiSys.service;

import com.snpp.MichiSys.entity.Venta;

import java.util.List;

public interface VentaService {

    List<Venta> listar();

    Venta buscarPorId(Long id);

    Venta guardar(Venta venta);

    Venta actualizar(Long id, Venta venta);

    void eliminar(Long id);
}