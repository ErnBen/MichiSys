package com.snpp.MichiSys.service;

import com.snpp.MichiSys.entity.Producto;

import java.util.List;

public interface ProductoService {

	List<Producto> listar();

	Producto buscarPorId(Long id);

	Producto guardar(Producto producto);

	Producto actualizar(Long id, Producto producto);

	void eliminar(Long id);

	List<Producto> listarActivos();

}