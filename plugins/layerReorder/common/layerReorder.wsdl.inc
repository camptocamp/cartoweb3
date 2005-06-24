      <!-- layerReorder -->

      <complexType name="LayerReorderInit">
        <all>
          <element name="layers" type="types:ArrayOfLayerInit"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerInit">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerInit[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LayerInit">
        <all>
          <element name="id" type="xsd:string"/>
          <element name="label" type="xsd:string"/>
        </all>
      </complexType>


      <complexType name="LayerReorderRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerIds" type="types:ArrayOfString"/>
        </all>
      </complexType>
