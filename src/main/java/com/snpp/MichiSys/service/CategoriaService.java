package com.snpp.MichiSys.service;

import com.snpp.MichiSys.entity.Categoria;

import java.util.List;

public interface CategoriaService {

    List<Categoria> listar();

    Categoria buscarPorId(Long id);

    Categoria guardar(Categoria categoria);

    Categoria actualizar(Long id, Categoria categoria);

    void eliminar(Long id);

}