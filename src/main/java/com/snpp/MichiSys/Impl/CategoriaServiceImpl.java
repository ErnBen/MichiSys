package com.snpp.MichiSys.Impl;

import com.snpp.MichiSys.entity.Categoria;
import com.snpp.MichiSys.repository.CategoriaRepository;
import com.snpp.MichiSys.service.CategoriaService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class CategoriaServiceImpl implements CategoriaService {

    @Autowired
    private CategoriaRepository categoriaRepository;

    @Override
    public List<Categoria> listar() {
        return categoriaRepository.findAll();
    }

    @Override
    public Categoria buscarPorId(Long id) {
        return categoriaRepository.findById(id).orElse(null);
    }

    @Override
    public Categoria guardar(Categoria categoria) {
        return categoriaRepository.save(categoria);
    }

    @Override
    public Categoria actualizar(Long id, Categoria categoria) {

        Categoria categoriaActual = categoriaRepository.findById(id).orElse(null);

        if (categoriaActual != null) {

            categoriaActual.setNombre(categoria.getNombre());
            categoriaActual.setDescripcion(categoria.getDescripcion());

            return categoriaRepository.save(categoriaActual);
        }

        return null;
    }

    @Override
    public void eliminar(Long id) {
        categoriaRepository.deleteById(id);
    }

}