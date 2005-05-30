      <!-- layers -->

      <complexType name="LayersRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerIds" type="types:ArrayOfLayerId"/>
          <element name="resolution" type="xsd:int"/>
        </all>
      </complexType>

      <complexType name="LayersResult">
        <all>
          <element name="className" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerId">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="xsd:string[]"/>
          </restriction>
        </complexContent>
      </complexType>
            
      <complexType name="ArrayOfChildrenSwitch">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:ChildrenSwitch[]"/>
          </restriction>
        </complexContent>
      </complexType>     

      <complexType name="ChildrenSwitch">
        <all>
          <element name="layers" type="types:ArrayOfLayerId" minOccurs="0"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfLayer">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Layer[]"/>
          </restriction>
        </complexContent>
      </complexType>     

      <complexType name="Layer">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="id" type="xsd:string"/>
          <element name="label" type="xsd:string"/>
          <element name="children" type="types:ArrayOfChildrenSwitch" minOccurs="0"/>
          <element name="minScale" type="xsd:double"/>
          <element name="maxScale" type="xsd:double"/>
          <element name="icon" type="xsd:string"/>
          <element name="link" type="xsd:string"/>
          <element name="aggregate" type="xsd:boolean" minOccurs="0"/>
          <element name="rendering" type="xsd:string" minOccurs="0"/>
          <element name="metadata" type="types:ArrayOfString" minOccurs="0"/>
        </all>
      </complexType>

      <complexType name="LayersInit">
        <all>
          <element name="className" type="xsd:string"/>

          <element name="notAvailableIcon" type="xsd:string"/>
          <element name="notAvailablePlusIcon" type="xsd:string"/>
          <element name="notAvailableMinusIcon" type="xsd:string"/>
          <element name="layers" type="types:ArrayOfLayer"/>

        </all>
      </complexType>