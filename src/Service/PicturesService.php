<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PicturesService
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params=$params;
    }

    public function add(UploadedFile $picture,?string $folder ='', ?int $width = 250, ?int $height = 250)
    {
        $fichier = md5(uniqid(rand(),true)).'.webp';

        $pictureInfos = getimagesize($picture);

        if($pictureInfos === false){
            throw new Exception("format d'image incorrect");

        }
        switch($pictureInfos['mime']){
            case 'image/png':
                $pictureSource = imagecreatefrompng($picture);
                break;
            case 'image/jpeg':
                $pictureSource = imagecreatefromjpeg($picture);
                break;
            case 'image/webp':
                $pictureSource = imagecreatefromwebp($picture);
                break;
            default:
                throw new Exception("format d'image incorrect");
        }
        $imageWidth = $pictureInfos[0];
        $imageHeight = $pictureInfos[1];

        switch($imageWidth <=> $imageHeight){
            case -1: //portrait
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = ($imageHeight - $squareSize) / 2;
                break;
            case 0: //carré
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = 0;
                break;
            case 1: //paysage
                $squareSize = $imageWidth;
                $src_x = ($imageHeight - $squareSize) / 2;
                $src_y = 0;
                break;
        }

        $resized_picture = imagecreatetruecolor($width,$height);

        imagecopyresampled($resized_picture,$pictureSource,0,0,$src_x,$src_y,$width,$height,$squareSize,$squareSize);

        $path = $this->params->get('images_directory').$folder;

        if(!file_exists($path . '/mini/')){
            mkdir($path . '/mini/',0755,true);
        }

        imagewebp($resized_picture,$path.'/mini/'.$width.'x'.$height.'-'.$fichier);

        $picture->move($path.'/',$fichier);

        return $fichier;
    }

    public function delete(string $fichier,?string $folder ='',?int $width = 250,?int $height = 250)
    {
        if($fichier !== 'default.webp'){
            $success = false;
            $path = $this->params->get('images_directory').$folder;

            $mini = $path.'/mini/'.$width.'x'.$height.'-'.$fichier;

            if(file_exists($mini)){
                unlink($mini);
                $success = true;
            }

            $original = $path.'/'.$fichier;

            if(file_exists($original)){
                unlink($original);
                $success = true;
            }

            return $success;
        }
        return false;
    }
}